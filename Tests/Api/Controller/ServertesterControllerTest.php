<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 FroxyNetwork
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author 0ddlyoko
 */

namespace Tests\Api\Controller;

use Api\Controller\DatasourceController\OAuth2DataController;
use Api\Controller\OAuth2Controller;
use Api\Controller\ServerController;
use Api\Controller\ServertesterController;
use http\Client;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\Request;
use OAuth2\Response;
use PHPUnit\Framework\TestCase;
use Tests\Util\DBUtil;
use Tests\Util\InputStreamImpl;
use Tests\Util\OAuthServerUtil;
use Tests\Util\RequestControllerImpl;
use Tests\Util\ResponseControllerImpl;
use Web\Controller\DBController;
use Web\Controller\ResponseController;
use Web\Core\Core;

class ServertesterControllerTest extends TestCase {

    /**
     * @var DBController $dbController;
     */
    private $dbController;

    /**
     * @var ServerController $serverController
     */
    private $serverController;

    /**
     * @var ServertesterController $serverTesterController
     */
    private $serverTesterController;

    /**
     * @var OAuthServerUtil $oauth
     */
    private $oauth;

    /**
     * @var OAuth2DataController $oauthStorage
     */
    private $oauthStorage;

    /**
     * @var InputStreamImpl $inputStreamUtil
     */
    private $inputStreamUtil;

    /**
     * @var ResponseControllerImpl $responseController
     */
    private $responseController;

    protected function setUp() {
        $this->dbController = new DBController('mongodb://127.0.0.1:27017', 'froxynetwork_test');
        Core::setDatabase($this->dbController);
        $this->serverController = Core::getAppController("Server");
        $this->serverTesterController = Core::getAppController("ServerTester");
        $this->oauthStorage = new OAuth2DataController($this->dbController->getDatabase());
        $this->oauth = new OAuthServerUtil($this->oauthStorage);
        $this->oauth->addGrantType(new ClientCredentials($this->oauthStorage));
        $this->serverController->oauth = $this->oauth;
        $this->serverTesterController->oauth = $this->oauth;
        $this->inputStreamUtil = new InputStreamImpl();
        $this->serverController->request = new RequestControllerImpl();
        $this->serverController->request->setInputStream($this->inputStreamUtil);
        $this->serverTesterController->request = new RequestControllerImpl();
        $this->serverTesterController->request->setInputStream($this->inputStreamUtil);
        $this->serverController->oauth_storage = $this->oauthStorage;
        $this->serverTesterController->oauth_storage = $this->oauthStorage;
        $this->responseController = new ResponseControllerImpl();
        $this->responseController->setEcho(false);
        $this->serverController->response = $this->responseController;
        $this->serverTesterController->response = $this->responseController;
        DBUtil::clearTables($this->dbController, 'froxynetwork_test');
    }

    function testGet() {
        // Add server (for test)
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        $server = $this->responseController->getLastData()['data'];

        // No permission
        $this->oauth->setBool(false);
        $this->serverTesterController->get("");
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_FORBIDDEN);
        $this->oauth->setBool(true);

        // Empty user_id
        $this->serverTesterController->get("/");
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Invalid user_id
        $this->serverTesterController->get("/INVALID");
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);

        // Empty client_id
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Invalid client_id
        $_GET['client_id'] = "ezdesddsq";
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Invalid client_id
        $_GET['client_id'] = "ezdesddsq";
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Bungeecord but use CLIENT_XXX
        $_GET['client_id'] = "CLIENT_XXX";
        $this->serverTesterController->get("/bungeecord");
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Bungeecord but without BUNGEE_XXX
        $_GET['client_id'] = "TEST_E";
        $this->serverTesterController->get("/bungeecord");
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Server but without SERVER_XXX
        $_GET['client_id'] = "TEST_XXX";
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);

        // No token
        $_GET['client_id'] = $server['auth']['client_id'];
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Invalid token
        $_GET['client_id'] = $server['auth']['client_id'];
        $_GET['token'] = "esdfezfsre";
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);

        // Client not found
        $_GET['client_id'] = "CLIENT_aaaa";
        $_GET['token'] = "aaaa";
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);

        // Create token
        $_SERVER['REQUEST_METHOD'] = "POST";
        $_POST['grant_type'] = "client_credentials";
        $_SERVER['PHP_AUTH_USER'] = $server['auth']['client_id'];
        $_SERVER['PHP_AUTH_PW'] = $server['auth']['client_secret'];
        $requ = $this->oauth->handleTokenRequest(Request::createFromGlobals());
        self::assertEquals(200, $requ->getStatusCode());
        $token = $requ->getParameter("access_token");
        self::assertNotEmpty($token);

        // client_id not found
        $_GET['client_id'] = "CLIENT_aaaa";
        $_GET['token'] = $token;
        $this->serverTesterController->get("/".$server['id']);
        $this->assertError($this->responseController->getLastData(), ResponseController::ERROR_UNAUTHORIZED);

        // Ok
        $_GET['client_id'] = $server['auth']['client_id'];
        $_GET['token'] = $token;
        $this->serverTesterController->get("/".$server['id']);
        $this->assertFalse($this->responseController->getLastData()['error']);

    }

    function assertError(array $data, int $code) {
        if (!isset($data) || empty($data))
            return true;
        self::assertTrue($data['error']);
        self::assertEquals($code, $data['code']);
    }
}
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
use Api\Controller\ServerController;
use PHPUnit\Framework\TestCase;
use Tests\Util\DBUtil;
use Tests\Util\InputStreamImpl;
use Tests\Util\OAuthServerUtil;
use Tests\Util\RequestControllerImpl;
use Tests\Util\ResponseControllerImpl;
use Web\Controller\DBController;
use Web\Controller\ResponseController;
use Web\Core\Core;

class ServerControllerTest extends TestCase {

    /**
     * @var DBController $dbController;
     */
    private $dbController;

    /**
     * @var ServerController $serverController
     */
    private $serverController;

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
        $this->oauth = new OAuthServerUtil();
        $this->serverController->oauth = $this->oauth;
        $this->inputStreamUtil = new InputStreamImpl();
        $this->serverController->request = new RequestControllerImpl();
        $this->serverController->request->setInputStream($this->inputStreamUtil);
        $this->oauthStorage = new OAuth2DataController($this->dbController->getDatabase());
        $this->serverController->oauth_storage = $this->oauthStorage;
        $this->responseController = new ResponseControllerImpl();
        $this->responseController->setEcho(false);
        $this->serverController->response = $this->responseController;
        DBUtil::clearTables($this->dbController, 'froxynetwork_test');
    }

    function testRandom() {
        self::assertEquals(32, strlen($this->serverController->generateAuthorizationCode(32)));
        $id = 1;
        $client = $this->serverController->generateClientSecret($id);
        self::assertEquals(2, count($client));
        self::assertTrue(Core::startsWith($client[0], "CLIENT_" . $id . "_"));
        self::assertTrue(Core::startsWith($client[1], "SECRET_" . $id . "_"));
        self::assertEquals(41, strlen($client[0]));
        self::assertEquals(41, strlen($client[1]));
        self::assertNotEquals(substr($client[0], 9, strlen($client[0]) - 9), substr($client[1], 9, strlen($client[1]) - 9));
    }

    function testPost() {
        // Empty data
        $this->inputStreamUtil->setText('');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not name
        $this->inputStreamUtil->setText('{"type":"KOTH","port":20001}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not port
        $this->inputStreamUtil->setText('{"name":"koth_1","type":"KOTH"}"');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not Type
        $this->inputStreamUtil->setText('{"name":"koth_1","port":20001}"');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Name not string
        $this->inputStreamUtil->setText('{"name":12, "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Name length not correct
        $this->inputStreamUtil->setText('{"name":"koth_1111111111111111111", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Type not string
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":12, "port":20001}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Type length not correct
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTHHHHHHHHHHHHHHHHHHHHH", "port":20001}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Port not int
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":"abcdef"}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Port not in range
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":0}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Port not in range
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":65536}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Correct
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        self::assertFalse($this->responseController->getLastData()['error']);

        // No permission
        $this->oauth->setBool(false);
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_FORBIDDEN);
        // Go Back
        $this->oauth->setBool(true);
    }

    function testGet() {
        // Add server (for test)
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        $server = $this->responseController->getLastData()['data'];

        // Empty data ==> All servers
        $this->serverController->get("/");
        self::assertFalse($this->responseController->getLastData()['error']);
        $servers = $this->responseController->getLastData()['data'];

        self::assertEquals(1, $servers['size']);
        self::assertEquals(1, count($servers['servers']));

        $srv = $servers['servers'][0];
        self::assertEquals($server['id'], $srv['id']);
        self::assertEquals("koth_1", $srv['name']);
        self::assertEquals("KOTH", $srv['type']);
        self::assertEquals("20001", $srv['port']);
        self::assertEquals($server['status'], $srv['status']);
        self::assertEquals($server['creationTime'], $srv['creationTime']);

        $this->inputStreamUtil->setText('{"name":"uhc_2", "type":"UHC", "port":20002}');
        $this->serverController->post("/");

        $this->serverController->get("/");
        self::assertEquals(2, $this->responseController->getLastData()['data']['size']);

        // Not int
        $this->serverController->get("/notInt");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);

        $this->serverController->get("/" . $server['id']);
        self::assertFalse($this->responseController->getLastData()['error']);
        $server2 = $this->responseController->getLastData()['data'];
        self::assertEquals($server['id'], $server2['id']);
        self::assertEquals($server['name'], $server2['name']);
        self::assertEquals($server['type'], $server2['type']);
        self::assertEquals($server['port'], $server2['port']);
        self::assertEquals($server['status'], $server2['status']);
        self::assertEquals($server['creationTime'], $server2['creationTime']);

        // Not exists
        $this->serverController->get("/99999");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);

        // Close server
        $this->serverController->delete("/" . $server['id']);
        self::assertFalse($this->responseController->getLastData()['error']);
        $this->serverController->get("/");
        self::assertEquals(1, $this->responseController->getLastData()['data']['size']);
    }

    function testPut() {
        // Add server (for test)
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        $id = $this->responseController->getLastData()['data']['id'];

        // Not id
        $this->inputStreamUtil->setText('{"status":"STARTED"}');
        $this->serverController->put("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not hex
        $this->inputStreamUtil->setText('{"status":"STARTED"}');
        $this->serverController->put("/5d290d06c7487b0ea0002bfG");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Empty data
        $this->inputStreamUtil->setText('');
        $this->serverController->put("/{$id}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not status
        $this->inputStreamUtil->setText('{"another":"other"}');
        $this->serverController->put("/{$id}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Status not string
        $this->inputStreamUtil->setText('{"status":1}');
        $this->serverController->put("/{$id}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Status not correct
        $this->inputStreamUtil->setText('{"status":"NOTASTATUS"}');
        $this->serverController->put("/{$id}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Server not found
        $this->inputStreamUtil->setText('{"status":"WAITING"}');
        $this->serverController->put("/aaaaaaaaaaaaaaaaaaaaaaaa");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);
        // Correct
        $this->inputStreamUtil->setText('{"status":"WAITING"}');
        $this->serverController->put("/{$id}");
        self::assertFalse($this->responseController->getLastData()['error']);
        // Incorrect (Cannot have an old or the same status)
        $this->inputStreamUtil->setText('{"status":"WAITING"}');
        $this->serverController->put("/{$id}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);

        // No permission
        $this->oauth->setBool(false);
        $this->inputStreamUtil->setText('{"status":"STARTED"}');
        $this->serverController->put("/{$id}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_FORBIDDEN);
        // Go Back
        $this->oauth->setBool(true);
    }

    function testDelete() {
        // Add server (for test)
        $this->inputStreamUtil->setText('{"name":"koth_1", "type":"KOTH", "port":20001}');
        $this->serverController->post("/");
        $id1 = $this->responseController->getLastData()['data']['id'];
        $this->inputStreamUtil->setText('{"name":"koth_2", "type":"KOTH", "port":20002}');
        $this->serverController->post("/");
        $id2 = $this->responseController->getLastData()['data']['id'];
        $this->inputStreamUtil->setText('{"name":"uhc_3", "type":"UHC", "port":20003}');
        $this->serverController->post("/");
        $id3 = $this->responseController->getLastData()['data']['id'];

        // Not id
        $this->inputStreamUtil->setText('');
        $this->serverController->delete("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not hex
        $this->serverController->delete("/5d290d06c7487b0ea0002bfG");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Server not found
        $this->serverController->delete("/aaaaaaaaaaaaaaaaaaaaaaaa");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);
        // Correct
        $this->serverController->delete("/{$id1}");
        self::assertFalse($this->responseController->getLastData()['error']);
        // Incorrect (Cannot delete again)
        $this->serverController->delete("/{$id1}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);

        $this->serverController->get("/");
        $srvs = $this->responseController->getLastData();
        self::assertFalse($srvs['error']);
        self::assertEquals(2, $srvs['data']['size']);

        // No permission
        $this->oauth->setBool(false);
        $this->inputStreamUtil->setText('{"status":"STARTED"}');
        $this->serverController->delete("/{$id2}");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_FORBIDDEN);
        // Go Back
        $this->oauth->setBool(true);
    }

    function assertError(array $data, int $code) {
        if (!isset($data) || empty($data))
            return true;
        self::assertTrue($data['error']);
        self::assertEquals($code, $data['code']);
    }
}
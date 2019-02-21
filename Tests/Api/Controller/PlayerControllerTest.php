<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 0ddlyoko
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
 */

namespace Tests\Api\Controller;

use Api\Controller\PlayerController;
use PHPUnit\Framework\TestCase;
use Tests\Util\DBUtil;
use Tests\Util\InputStreamImpl;
use Tests\Util\OAuthServerUtil;
use Tests\Util\RequestControllerImpl;
use Tests\Util\ResponseControllerImpl;
use Web\Controller\DBController;
use Web\Controller\ResponseController;
use Web\Core\Core;

class PlayerControllerTest extends TestCase {

    /**
     * @var DBController $dbController;
     */
    private $dbController;

    /**
     * @var PlayerController $playerController
     */
    private $playerController;

    /**
     * @var OAuthServerUtil $oauth
     */
    private $oauth;

    /**
     * @var InputStreamImpl $inputStreamUtil
     */
    private $inputStreamUtil;

    /**
     * @var ResponseControllerImpl $responseController
     */
    private $responseController;

    protected function setUp() {
        $this->dbController = new DBController('localhost', 3306, 'root', '', 'froxynetwork_test');
        Core::setDatabase($this->dbController);
        $this->playerController = Core::getAppController("Player");
        $this->oauth = new OAuthServerUtil();
        $this->playerController->oauth = $this->oauth;
        $this->inputStreamUtil = new InputStreamImpl();
        $this->playerController->request = new RequestControllerImpl();
        $this->playerController->request->setInputStream($this->inputStreamUtil);
        $this->responseController = new ResponseControllerImpl();
        $this->responseController->setEcho(false);
        $this->playerController->response = $this->responseController;
        DBUtil::clearTables($this->dbController);
    }

    function testPost() {
        // Empty data
        $this->inputStreamUtil->setText('');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not UUID
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not Pseudo
        $this->inputStreamUtil->setText('{"uuid":"86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Not Ip
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // UUID length not correct
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // UUID value not correct
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fag", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Pseudo length not correct
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyokoeeeeeeeeeeeeeeeeeeeee", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Ip length not correct
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko", "ip":"1.0.0."}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Ip format not correct
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko", "ip":"127.0.0."}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Correct
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertFalse($this->responseController->getLastData()['error']);
        $user = $this->responseController->getLastData()['data'];
        self::assertEquals("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", $user['uuid']);
        self::assertEquals("0ddlyoko", $user['pseudo']);
        self::assertEquals("127.0.0.1", $user['ip']);
        // UUID already exists
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_CONFLICT);
        // Pseudo already exists
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa6", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_CONFLICT);
        // No permission
        $this->oauth->setBool(false);
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa8", "pseudo":"1ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_FORBIDDEN);
        // Go Back
        $this->oauth->setBool(true);
    }

    function testGet() {
        // Add player (for test)
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");

        // No player
        $this->playerController->get("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);

        // Player not found
        $this->playerController->get("/1ddlyoko");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);
        // UUID not found
        $this->playerController->get("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);

        // Player exist
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);
        $this->playerController->get("/0ddlyoko");
        self::assertFalse($this->responseController->getLastData()['error']);
        $user = $this->responseController->getLastData()['data'];
        self::assertEquals("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", $user['uuid']);
        self::assertEquals("0ddlyoko", $user['pseudo']);
        self::assertEquals("127.0.0.1", $user['ip']);
        self::assertEquals("0ddlyoko", $user['displayName']);

        // No permission
        $this->oauth->setBool(false);

        $this->playerController->get("/0ddlyoko");
        self::assertFalse($this->responseController->getLastData()['error']);
        $user = $this->responseController->getLastData()['data'];
        self::assertEquals("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", $user['uuid']);
        self::assertEquals("0ddlyoko", $user['pseudo']);
        self::assertEquals("<HIDDEN>", $user['ip']);
        self::assertEquals("<HIDDEN>", $user['displayName']);

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
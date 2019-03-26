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

    function testPut() {
        // Add player (for test)
        $this->inputStreamUtil->setText('{"uuid": "86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "pseudo":"0ddlyoko", "ip":"127.0.0.1"}');
        $this->playerController->post("/");

        // No permission
        $this->oauth->setBool(false);
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko", "displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate( new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_FORBIDDEN);

        // Go Back
        $this->oauth->setBool(true);

        // Invalid length
        $this->playerController->put("/");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        // Invalid length
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Empty data
        $this->inputStreamUtil->setText('');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Error
        $this->inputStreamUtil->setText('notjson');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Not pseudo
        $this->inputStreamUtil->setText('{"displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Not displayName
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Not lastLogin
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Not ip
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Not lang
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid UUID
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fag");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid Pseudo length
        $this->inputStreamUtil->setText('{"pseudo":"","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid Pseudo length
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyokoooooooooooooo","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid DisplayName length
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid DisplayName length
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyokoooooooooooooo", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid Coins
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":-1, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid Level
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":-1, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid Exp
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":-1, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid LastLogin format
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"INVALID", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Invalid Ip
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // Player not found
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate(new \DateTime()).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa6");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_NOTFOUND);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // LastLogin < current LastLogin
        $dateTime = new \DateTime();
        $dateTime->add(\DateInterval::createFromDateString('yesterday'));
        $this->inputStreamUtil->setText('{"pseudo":"0ddlyoko","displayName":"0ddlyoko", "coins":5, "level":2, "exp":12, "lastLogin":"'.Core::formatDate($dateTime).'", "ip":"127.0.0.1", "lang":"fr_FR"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertError($this->responseController->getLastData(), ResponseController::ERROR_BAD_REQUEST);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);

        // All is ok
        $dateTime = new \DateTime();
        $dateTime->add(new \DateInterval("P1D"));
        $this->inputStreamUtil->setText('{"pseudo":"1ddlyoko","displayName":"1ddlyoko", "coins":50, "level":20, "exp":120, "lastLogin":"'.Core::formatDate($dateTime).'", "ip":"127.0.0.2", "lang":"en_US"}');
        $this->playerController->put("/86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertFalse($this->responseController->getLastData()['error']);
        $user = $this->responseController->getLastData()['data'];
        self::assertEquals("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", $user['uuid']);
        self::assertEquals("1ddlyoko", $user['pseudo']);
        self::assertEquals("1ddlyoko", $user['displayName']);
        self::assertEquals(50, $user['coins']);
        self::assertEquals(20, $user['level']);
        self::assertEquals(120, $user['exp']);
        self::assertEquals(Core::formatDate($dateTime), $user['lastLogin']);
        self::assertEquals("127.0.0.2", $user['ip']);
        self::assertEquals("en_US", $user['lang']);
    }

    function assertError(array $data, int $code) {
        if (!isset($data) || empty($data))
            return true;
        self::assertTrue($data['error']);
        self::assertEquals($code, $data['code']);
    }
}
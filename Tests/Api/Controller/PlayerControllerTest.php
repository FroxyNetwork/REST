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
use Web\Core\Core;

class PlayerControllerTest extends TestCase {

    /**
     * @var InputStreamImpl $inputStreamUtil
     */
    private $inputStreamUtil;

    /**
     * @var DBController $dbController;
     */
    private $dbController;

    /**
     * @var PlayerController $playerController
     */
    private $playerController;

    /**
     * @var ResponseControllerImpl $responseController
     */
    private $responseController;

    protected function setUp() {
        $this->dbController = new DBController('localhost', 3306, 'root', '', 'froxynetwork_test');
        Core::setDatabase($this->dbController);
        $this->playerController = Core::getAppController("Player");
        $this->playerController->oauth = new OAuthServerUtil();
        $this->inputStreamUtil = new InputStreamImpl();
        $this->playerController->request = new RequestControllerImpl();
        $this->playerController->request->setInputStream($this->inputStreamUtil);
        $this->responseController = new ResponseControllerImpl();
        $this->playerController->response = $this->responseController;
        DBUtil::clearTables($this->dbController);
    }

    function testPost() {
        // Empty data
        $this->inputStreamUtil->setText('');
        $this->playerController->post("/");
        self::assertError($this->responseController->getLastData(), 400);
    }

    function assertError(array $data, int $code) {
        if (!isset($data) || empty($data))
            return true;
        self::assertTrue($data['error']);
        self::assertEquals($code, $data['code']);
    }
}
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

namespace Tests\Api\Controller\DatasourceController;


use Api\Controller\DatasourceController\PlayerDataController;
use PHPUnit\Framework\TestCase;
use Tests\Util\DBUtil;
use Web\Controller\DBController;
use Web\Core\Core;

class PlayerDataControllerTest extends TestCase {

    /**
     * @var DBController $dbController;
     */
    private $dbController;

    /**
     * @var PlayerDataController $playerDataController
     */
    private $playerDataController;

    protected function setUp() {
        $this->dbController = new DBController('localhost', 3306, 'root', '', 'froxynetwork_test');
        Core::setDatabase($this->dbController);
        $this->playerDataController = Core::getDataController("Player");
    }

    function testCreateUser() {
        $this->playerDataController->createUser("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "0ddlyoko", "127.0.0.1");
        $oddlyoko = $this->playerDataController->getUserByUUID("86173d9f-f7f4-4965-8e9d-f37783bf6fa7");
        self::assertNotNull($oddlyoko);
        self::assertNotFalse($oddlyoko);
        DBUtil::clearTables($this->dbController);
    }
}
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

use Api\Controller\ServerController;
use PHPUnit\Framework\TestCase;
use Web\Core\Core;

class ServerControllerTest extends TestCase {
    /**
     * @var ServerController $serverController
     */
    private $serverController;

    protected function setUp() {
        $this->serverController = Core::getAppController("Server");
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
}
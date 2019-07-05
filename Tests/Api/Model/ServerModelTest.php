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

namespace Tests\Api\Model;

use Api\Model\PlayerModel;
use Api\Model\ServerModel;
use PHPUnit\Framework\TestCase;

class ServerModelTest extends TestCase {

    function testGetServer() {
        $now = new \DateTime('now');
        $server = new ServerModel(1, "koth_01", "KOTH", 25565, "STARTING", $now);
        self::assertEquals(1, $server->getId());
        self::assertEquals("koth_01", $server->getName());
        self::assertEquals("KOTH", $server->getType());
        self::assertEquals(25565, $server->getPort());
        self::assertEquals("STARTING", $server->getStatus());
        self::assertEquals($now, $server->getCreationTime());
    }

    function testSetServer() {
        $now = new \DateTime('now');
        $server = new ServerModel(1, "koth_01", "KOTH", 25565, "STARTING", $now);

        $server->setId(2);
        $server->setStatus("STARTED");

        self::assertEquals(2, $server->getId());
        self::assertEquals("STARTED", $server->getStatus());
    }
}
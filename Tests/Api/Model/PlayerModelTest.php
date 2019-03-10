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

namespace Tests\Api\Model;

use Api\Model\PlayerModel;
use PHPUnit\Framework\TestCase;

class PlayerModelTest extends TestCase {

    function testGetPlayer() {
        $now = new \DateTime('now');
        $player = new PlayerModel("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "0ddlyoko", "1ddlyoko", 100, 10, 125, $now, $now, "127.0.0.1", "fr_FR");
        self::assertEquals("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", $player->getUuid());
        self::assertEquals("0ddlyoko", $player->getPseudo());
        self::assertEquals("1ddlyoko", $player->getDisplayName());
        self::assertEquals(100, $player->getCoins());
        self::assertEquals(10, $player->getLevel());
        self::assertEquals(125, $player->getExp());
        self::assertEquals($now, $player->getFirstLogin());
        self::assertEquals($now, $player->getLastLogin());
        self::assertEquals("127.0.0.1", $player->getIp());
        self::assertEquals("fr_FR", $player->getLang());
    }

    function testSetPlayer() {
        $now = new \DateTime('now');
        $player = new PlayerModel("86173d9f-f7f4-4965-8e9d-f37783bf6fa7", "0ddlyoko", "1ddlyoko", 100, 10, 125, $now, $now, "127.0.0.1", "fr_FR");
        $player->setPseudo("1ddlyoko");
        $player->setDisplayName("0ddlyoko");
        $player->setCoins(101);
        $player->setLevel(11);
        $player->setExp(126);
        $player->setLastLogin($now);
        $player->setIp("127.0.0.2");
        $player->setLang("fr_BE");

        self::assertEquals("1ddlyoko", $player->getPseudo());
        self::assertEquals("0ddlyoko", $player->getDisplayName());
        self::assertEquals(101, $player->getCoins());
        self::assertEquals(11, $player->getLevel());
        self::assertEquals(126, $player->getExp());
        self::assertEquals($now, $player->getFirstLogin());
        self::assertEquals($now, $player->getLastLogin());
        self::assertEquals("127.0.0.2", $player->getIp());
        self::assertEquals("fr_BE", $player->getLang());
    }
}
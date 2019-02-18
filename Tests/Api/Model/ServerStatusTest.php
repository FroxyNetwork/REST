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

use Api\Model\ServerModel;
use Api\Model\ServerStatus;
use PHPUnit\Framework\TestCase;

class ServerStatusTest extends TestCase {

    function testEnum() {
        self::assertEquals("STARTING", ServerStatus::STARTING);
        self::assertEquals("WAITING", ServerStatus::WAITING);
        self::assertEquals("STARTED", ServerStatus::STARTED);
        self::assertEquals("ENDING", ServerStatus::ENDING);
        self::assertEquals("ENDED", ServerStatus::ENDED);
    }

    function testIsAfter() {
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTING, ServerStatus::STARTING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTING, ServerStatus::WAITING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTING, ServerStatus::STARTED));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTING, ServerStatus::ENDING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTING, ServerStatus::ENDED));

        self::assertTrue(ServerStatus::isAfter(ServerStatus::WAITING, ServerStatus::STARTING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::WAITING, ServerStatus::WAITING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::WAITING, ServerStatus::STARTED));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::WAITING, ServerStatus::ENDING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::WAITING, ServerStatus::ENDED));

        self::assertTrue(ServerStatus::isAfter(ServerStatus::STARTED, ServerStatus::STARTING));
        self::assertTrue(ServerStatus::isAfter(ServerStatus::STARTED, ServerStatus::WAITING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTED, ServerStatus::STARTED));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTED, ServerStatus::ENDING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::STARTED, ServerStatus::ENDED));

        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDING, ServerStatus::STARTING));
        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDING, ServerStatus::WAITING));
        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDING, ServerStatus::STARTED));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::ENDING, ServerStatus::ENDING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::ENDING, ServerStatus::ENDED));

        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDED, ServerStatus::STARTING));
        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDED, ServerStatus::WAITING));
        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDED, ServerStatus::STARTED));
        self::assertTrue(ServerStatus::isAfter(ServerStatus::ENDED, ServerStatus::ENDING));
        self::assertFalse(ServerStatus::isAfter(ServerStatus::ENDED, ServerStatus::ENDED));
    }

    function testIsValidName() {
        self::assertTrue(ServerStatus::isValidName("STARTING"));
        self::assertTrue(ServerStatus::isValidName("WAITING"));
        self::assertTrue(ServerStatus::isValidName("STARTED"));
        self::assertTrue(ServerStatus::isValidName("ENDING"));
        self::assertTrue(ServerStatus::isValidName("ENDED"));
        self::assertFalse(ServerStatus::isValidName("ENDEDE"));
        self::assertFalse(ServerStatus::isValidName(""));
        self::assertFalse(ServerStatus::isValidName(null));
        self::assertFalse(ServerStatus::isValidName([]));

        self::assertTrue(ServerStatus::isValidName("STARTINg"));
        self::assertFalse(ServerStatus::isValidName("STARTINg", true));
        self::assertFalse(ServerStatus::isValidName("startede", true));
        self::assertTrue(ServerStatus::isValidName("ending", false));
        self::assertFalse(ServerStatus::isValidName("ending", true));
    }

    function testIsValidValue() {
        self::assertTrue(ServerStatus::isValidValue("STARTING"));
        self::assertTrue(ServerStatus::isValidValue("WAITING"));
        self::assertTrue(ServerStatus::isValidValue("STARTED"));
        self::assertTrue(ServerStatus::isValidValue("ENDING"));
        self::assertTrue(ServerStatus::isValidValue("ENDED"));
        self::assertFalse(ServerStatus::isValidValue("ENDEDE"));
        self::assertFalse(ServerStatus::isValidValue(""));
        self::assertFalse(ServerStatus::isValidValue(null));
        self::assertFalse(ServerStatus::isValidValue([]));

        self::assertFalse(ServerStatus::isValidValue("STARTINg"));
        self::assertFalse(ServerStatus::isValidValue("STARTINg", true));
        self::assertFalse(ServerStatus::isValidValue("startede", false));
        self::assertTrue(ServerStatus::isValidValue("ending", false));
        self::assertFalse(ServerStatus::isValidValue("ending", true));
    }
}
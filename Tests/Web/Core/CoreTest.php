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

namespace Tests\Web\Core;

use PHPUnit\Framework\TestCase;
use Web\Core\Core;

class CoreTest extends TestCase {
    
    function testFileName() {
        self::assertEquals("TestModel", Core::fileName(Core::MODELS, "test"));
        self::assertEquals("TestModel", Core::fileName(Core::MODELS, "Test"));
        self::assertEquals("TestModel", Core::fileName(Core::MODELS, "TEST"));
        self::assertEquals("TestModel", Core::fileName(Core::MODELS, "tEST"));
        self::assertEquals("TestController", Core::fileName(Core::CONTROLLERS, "test"));
        self::assertEquals("TestController", Core::fileName(Core::CONTROLLERS, "Test"));
        self::assertEquals("TestController", Core::fileName(Core::CONTROLLERS, "TEST"));
        self::assertEquals("TestController", Core::fileName(Core::CONTROLLERS, "tEST"));
        self::assertEquals("TestDataController", Core::fileName(Core::DATASOURCES, "test"));
        self::assertEquals("TestDataController", Core::fileName(Core::DATASOURCES, "Test"));
        self::assertEquals("TestDataController", Core::fileName(Core::DATASOURCES, "TEST"));
        self::assertEquals("TestDataController", Core::fileName(Core::DATASOURCES, "tEST"));
        self::assertEquals("Test" , Core::fileName("NOMINEXISTANT", "Test"));
        self::assertEquals("Test" , Core::fileName("", "Test"));
        self::assertEquals("Test" , Core::fileName(5, "Test"));
        self::assertEquals("Test" , Core::fileName(5, "TEST"));
        self::assertEquals("" , Core::fileName(Core::MODELS, ""));
        self::assertEquals(null , Core::fileName(Core::MODELS, null));
    }

    function testPath() {
        self::assertEquals(API_DIR.DS."model".DS."TestModel.php", Core::path(Core::MODELS, "test"));
        self::assertEquals(API_DIR.DS."model".DS."TestModel.php", Core::path(Core::MODELS, "Test"));
        self::assertEquals(API_DIR.DS."model".DS."TestModel.php", Core::path(Core::MODELS, "TEST"));
        self::assertEquals(API_DIR.DS."model".DS."TestModel.php", Core::path(Core::MODELS, "tEST"));
        self::assertEquals(API_DIR.DS."controller".DS."TestController.php", Core::path(Core::CONTROLLERS, "test"));
        self::assertEquals(API_DIR.DS."controller".DS."TestController.php", Core::path(Core::CONTROLLERS, "Test"));
        self::assertEquals(API_DIR.DS."controller".DS."TestController.php", Core::path(Core::CONTROLLERS, "TEST"));
        self::assertEquals(API_DIR.DS."controller".DS."TestController.php", Core::path(Core::CONTROLLERS, "tEST"));
        self::assertEquals(API_DIR.DS."controller".DS."datasourceController".DS."TestDataController.php", Core::path(Core::DATASOURCES, "test"));
        self::assertEquals(API_DIR.DS."controller".DS."datasourceController".DS."TestDataController.php", Core::path(Core::DATASOURCES, "Test"));
        self::assertEquals(API_DIR.DS."controller".DS."datasourceController".DS."TestDataController.php", Core::path(Core::DATASOURCES, "TEST"));
        self::assertEquals(API_DIR.DS."controller".DS."datasourceController".DS."TestDataController.php", Core::path(Core::DATASOURCES, "tEST"));
        self::assertEquals(API_DIR.DS."url".DS."Test.php", Core::path("url", "tEST"));
        self::assertEquals(API_DIR.DS."5".DS."Test.php", Core::path(5, "tEST"));
        self::assertEquals(API_DIR.DS."model".DS.".php", Core::path(Core::MODELS, ""));
        self::assertEquals(API_DIR.DS."model".DS.".php", Core::path(Core::MODELS, null));
    }

    function testStartWith() {
        self::assertFalse(Core::startsWith("test1234", null));
        self::assertTrue(Core::startsWith("test1234", ""));
        self::assertTrue(Core::startsWith("test1234", "t"));
        self::assertTrue(Core::startsWith("test1234", "test"));
        self::assertFalse(Core::startsWith("test1234", "est"));
        self::assertTrue(Core::startsWith("test1234", "test1234"));
        self::assertFalse(Core::startsWith("test1234", "test12345"));
        self::assertTrue(Core::startsWith("", ""));
        self::assertFalse(Core::startsWith("", "test"));
        self::assertFalse(Core::startsWith(null, "test"));
        self::assertFalse(Core::startsWith(null, ""));
        self::assertTrue(Core::startsWith(null, null));
        self::assertTrue(Core::startsWith("CLIENT_1_6ee56abb0d32f781f4aaf4b8b0283744", "CLIENT_1_"));
    }

    function testEndWith() {
        self::assertFalse(Core::endsWith("test1234", null));
        self::assertTrue(Core::endsWith("test1234", ""));
        self::assertTrue(Core::endsWith("test1234", "4"));
        self::assertTrue(Core::endsWith("test1234", "1234"));
        self::assertTrue(Core::endsWith("test1234", "test1234"));
        self::assertFalse(Core::endsWith("test1234", "test12345"));
        self::assertTrue(Core::endsWith("", ""));
        self::assertFalse(Core::endsWith("", "test"));
        self::assertFalse(Core::endsWith(null, "test"));
        self::assertFalse(Core::endsWith(null, ""));
        self::assertTrue(Core::endsWith(null, null));
    }

    function testFormatDate() {
        $now = new \DateTime();
        $time = $now->getTimestamp();
        self::assertEquals(date('Y', $time).'-'.date('m', $time).'-'.date('d', $time).' '.date('H', $time).':'.date('i', $time).':'.date('s', $time), Core::formatDate($now));
        self::assertEquals($time, strtotime(Core::formatDate($now)));
        self::assertEquals("", Core::formatDate(null));
    }

    function testIsInteger() {
        self::assertTrue(Core::isInteger(0));
        self::assertTrue(Core::isInteger(10));
        // We don't need to check if negative value is int or not in this program
        // Maybe in the futur ?
        //self::assertTrue(Core::isInteger(-10));
        self::assertTrue(Core::isInteger("0"));
        self::assertTrue(Core::isInteger("10"));
        self::assertTrue(Core::isInteger("999999999999999999999999999999"));

        self::assertFalse(Core::isInteger("string"));
        self::assertFalse(Core::isInteger("10e4"));
        self::assertFalse(Core::isInteger(""));
        self::assertFalse(Core::isInteger(null));
    }
}
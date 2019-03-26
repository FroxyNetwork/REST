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

namespace Tests\Web\Core;


use PHPUnit\Framework\TestCase;
use Web\Core\Route;

class RouteTest extends TestCase {

    function testRoute() {
        // / ==> TestController
        Route::connect("/", "Test");
        self::assertEquals([
            "controller" => "Test",
            "param" => ""
        ], Route::getRoute("/"));
        self::assertEquals([
            "controller" => "Test",
            "param" => "/a"
        ], Route::getRoute("/a"));
        self::assertEquals([
            "controller" => "Test",
            "param" => "/a/a"
        ], Route::getRoute("/a/a"));
        self::assertEquals([
            "controller" => "Test",
            "param" => "/a/a/a/A/E/F"
        ], Route::getRoute("/a/a/a/A/E/F"));

        Route::connect("/user", "User");
        self::assertEquals([
            "controller" => "Test",
            "param" => ""
        ], Route::getRoute("/"));
        self::assertEquals([
            "controller" => "Test",
            "param" => "/use"
        ], Route::getRoute("/use"));
        self::assertEquals([
            "controller" => "User",
            "param" => ""
        ], Route::getRoute("/user"));
        // Uppercase
        self::assertEquals([
            "controller" => "User",
            "param" => "/F"
        ], Route::getRoute("/USER/F"));

        Route::connect("/user/get", "UserGet");
        self::assertEquals([
            "controller" => "UserGet",
            "param" => "/0ddlyoko"
        ], Route::getRoute("/user/get/0ddlyoko"));
        self::assertEquals([
            "controller" => "User",
            "param" => ""
        ], Route::getRoute("/user"));
    }
}
<?php
/**
 * Created by IntelliJ IDEA.
 * User: nagi
 * Date: 01/03/2019
 * Time: 14:01
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
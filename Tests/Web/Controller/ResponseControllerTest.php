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
use Tests\Util\ResponseControllerImpl;

class ResponseTest extends TestCase {

    /**
     * @var ResponseControllerImpl
     */
    private $responseController;

    protected function setUp() {
        $this->responseController = new ResponseControllerImpl();
    }

    function test_const() {
        self::assertEquals(200, $this->responseController::SUCCESS_OK);
        self::assertEquals(201, $this->responseController::SUCCESS_CREATED);
        self::assertEquals(202, $this->responseController::SUCCESS_ACCEPTED);
        self::assertEquals(204, $this->responseController::SUCCESS_NO_CONTENT);
        self::assertEquals(301, $this->responseController::REDIRECT_PERMANENTLY);
        self::assertEquals(302, $this->responseController::REDIRECT_FOUND);
        self::assertEquals(304, $this->responseController::REDIRECT_NOT_MODIFIED);
        self::assertEquals(307, $this->responseController::REDIRECT_TEMPORARY);
        self::assertEquals(400, $this->responseController::ERROR_BAD_REQUEST);
        self::assertEquals(401, $this->responseController::ERROR_UNAUTHORIZED);
        self::assertEquals(403, $this->responseController::ERROR_FORBIDDEN);
        self::assertEquals(404, $this->responseController::ERROR_NOTFOUND);
        self::assertEquals(500, $this->responseController::SERVER_INTERNAL);
        self::assertEquals(501, $this->responseController::SERVER_NOT_IMPLEMENTED);
    }

    function test_create() {
        self::assertEquals([
            "error" => false,
            "code" => 200,
            "error_message" => null,
            "data" => []
        ], $this->responseController::_create([]));
        self::assertEquals([
            "error" => false,
            "code" => 201,
            "error_message" => null,
            "data" => []
        ], $this->responseController::_create([], 201));
        self::assertEquals([
            "error" => true,
            "code" => 200,
            "error_message" => null,
            "data" => []
        ], $this->responseController::_create([], 200, true));
        self::assertEquals([
            "error" => false,
            "code" => 200,
            "error_message" => "Error",
            "data" => []
        ], $this->responseController::_create([], 200, false, "Error"));
        self::assertEquals([
            "error" => false,
            "code" => 200,
            "error_message" => "Error",
            "data" => [
                "test" => "test2",
                "number" => 4,
                "bool" => false,
                "array" => [
                    "value" => 4
                ]
            ]
        ], $this->responseController::_create([
            "test" => "test2",
            "number" => 4,
            "bool" => false,
            "array" => [
                "value" => 4
            ]
        ], 200, false, "Error"));
        self::assertNotEquals([
            "error" => false,
            "code" => 200,
            "error_message" => "Error",
            "data" => [
                "test" => "test2",
                "number" => 4,
                "bool" => false,
                "array" => [
                    "value" => 5 // Not same here
                ]
            ]
        ], $this->responseController::_create([
            "test" => "test2",
            "number" => 4,
            "bool" => false,
            "array" => [
                "value" => 4
            ]
        ], 200, false, "Error"));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function test_create_exception() {
        $this->responseController::_create([], "e");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function test_create_exception2() {
        $this->responseController::_create([], $this->responseController::SUCCESS_OK, "e");
    }

    function test_ok() {
        self::ok_test(["data" => "value"]);
        self::ok_test(["data" => [
            "test" => "test2",
            "number" => 4,
            "bool" => false,
            "array" => [
                "value" => 4
            ]
        ]]);
        self::ok_test_not(["data" => [
            "test" => "test2",
            "number" => 4,
            "bool" => false,
            "array" => [
                "value" => 5
            ]
        ]], [
            "error" => false,
            "code" => 200,
            "error_message" => null,
            "data" => [
                "data" => [
                    "test" => "test2",
                    "number" => 4,
                    "bool" => false,
                    "array" => [
                        "value" => 4
                    ]
                ]
            ]
        ]);
    }

    function ok_test($data, $code = 200) {
        ob_start();
        $this->responseController->ok($data);
        self::assertEquals(json_encode([
            "error" => false,
            "code" => $code,
            "error_message" => null,
            "data" => $data
        ]), ob_get_clean());
    }

    function ok_test_not($data, $result) {
        ob_start();
        $this->responseController->ok($data);
        self::assertNotEquals(json_encode($result), ob_get_clean());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function test_ok_exception() {
        $this->responseController->ok([], "e");
    }

    function test_error() {
        self::error_test("Erreur", $this->responseController::ERROR_BAD_REQUEST);
    }

    function error_test($message, $code) {
        ob_start();
        $this->responseController->error($code, $message);
        self::assertEquals(json_encode([
            "error" => true,
            "code" => $code,
            "error_message" => $message,
            "data" => null
        ]), ob_get_clean());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function test_error_exception() {
        $this->responseController->error("e", "Erreur");
    }

    function test_notImplemented() {
        ob_start();
        $this->responseController->notImplemented();
        $arr = json_decode(ob_get_clean(), true);
        self::assertTrue(is_array($arr));
        self::assertTrue($arr["error"]);
        self::assertEquals($this->responseController::SERVER_NOT_IMPLEMENTED, $arr["code"]);
        self::assertNotEmpty($arr["error_message"]);
        self::assertNull($arr["data"]);
    }
}
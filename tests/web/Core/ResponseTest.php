<?php
/**
 * Created by IntelliJ IDEA.
 * User: natha
 * Date: 16-01-19
 * Time: 15:23
 */

namespace Tests\Web\Core;

use PHPUnit\Framework\TestCase;
use Web\Core\Response;

class ResponseTest extends TestCase {

    protected function setUp() {
        Response::test(true);
    }

    function test_const() {
        self::assertEquals(200, Response::SUCCESS_OK);
        self::assertEquals(201, Response::SUCCESS_CREATED);
        self::assertEquals(202, Response::SUCCESS_ACCEPTED);
        self::assertEquals(204, Response::SUCCESS_NO_CONTENT);
        self::assertEquals(301, Response::REDIRECT_PERMANENTLY);
        self::assertEquals(302, Response::REDIRECT_FOUND);
        self::assertEquals(304, Response::REDIRECT_NOT_MODIFIED);
        self::assertEquals(307, Response::REDIRECT_TEMPORARY);
        self::assertEquals(400, Response::ERROR_BAD_REQUEST);
        self::assertEquals(401, Response::ERROR_UNAUTHORIZED);
        self::assertEquals(403, Response::ERROR_FORBIDDEN);
        self::assertEquals(404, Response::ERROR_NOTFOUND);
        self::assertEquals(500, Response::SERVER_INTERNAL);
        self::assertEquals(501, Response::SERVER_NOT_IMPLEMENTED);
    }

    function test_create() {
        self::assertEquals([
            "error" => false,
            "code" => 200,
            "error_message" => null,
            "data" => []
        ], Response::_create([]));
        self::assertEquals([
            "error" => false,
            "code" => 201,
            "error_message" => null,
            "data" => []
        ], Response::_create([], 201));
        self::assertEquals([
            "error" => true,
            "code" => 200,
            "error_message" => null,
            "data" => []
        ], Response::_create([], 200, true));
        self::assertEquals([
            "error" => false,
            "code" => 200,
            "error_message" => "Error",
            "data" => []
        ], Response::_create([], 200, false, "Error"));
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
        ], Response::_create([
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
        ], Response::_create([
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
        Response::_create([], "e");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function test_create_exception2() {
        Response::_create([], Response::SUCCESS_OK, "e");
    }

    function test_ok() {
        $this->ok_test(["data" => "value"]);
        $this->ok_test(["data" => [
            "test" => "test2",
            "number" => 4,
            "bool" => false,
            "array" => [
                "value" => 4
            ]
        ]]);
        $this->ok_test_not(["data" => [
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
        Response::ok($data);
        self::assertEquals(json_encode([
            "error" => false,
            "code" => $code,
            "error_message" => null,
            "data" => $data
        ]), ob_get_clean());
    }

    function ok_test_not($data, $result) {
        ob_start();
        Response::ok($data);
        self::assertNotEquals(json_encode($result), ob_get_clean());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function test_ok_exception() {
        Response::ok([], "e");
    }

    function test_error() {
        self::error_test("Erreur", Response::ERROR_BAD_REQUEST);
    }

    function error_test($message, $code) {
        ob_start();
        Response::error($code, $message);
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
        Response::error("e", "Erreur");
    }

    function test_notImplemented() {
        ob_start();
        Response::notImplemented();
        $arr = json_decode(ob_get_clean(), true);
        self::assertIsArray($arr);
        self::assertTrue($arr["error"]);
        self::assertEquals(Response::SERVER_NOT_IMPLEMENTED, $arr["code"]);
        self::assertNotEmpty($arr["error_message"]);
        self::assertNull($arr["data"]);
    }
}
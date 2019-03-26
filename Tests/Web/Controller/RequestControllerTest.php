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

namespace Tests\Web\Controller;

use PHPUnit\Framework\TestCase;
use Web\Controller\RequestController;

class RequestControllerTest extends TestCase {
    /**
     * @var RequestController
     */
    private $request;

    protected function setUp() {
        // URL: http://127.0.0.1:80/a/path/file.php
        $_SERVER['HTTP_HOST'] = "127.0.0.1";
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = "/a/path/file.php?option=test&option2=true";
        $_SERVER['QUERY_STRING'] = "option=test&option2=true";
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_GET["option"] = "test";
        $_GET["option2"] = true;
        $_POST["aaaa"] = "bbbb";
        $this->request = new RequestController();
    }

    function test_methods() {
        self::assertEquals("GET", $this->request->getMethod());
        self::assertFalse($this->request->isHttps());
        self::assertEquals("127.0.0.1", $this->request->getHost());
        self::assertEquals(80, $this->request->getPort());
        self::assertEquals("/a/path/", $this->request->getPath());
        self::assertEquals("file.php", $this->request->getFile());
        self::assertEquals("127.0.0.1/a/path/file.php", $this->request->getURL());
        self::assertEquals("http://127.0.0.1/a/path/file.php", $this->request->getURL(true));
        self::assertEquals("http://127.0.0.1/a/path/file.php?option=test&option2=true", $this->request->getURL(true, true));
        self::assertEquals("option=test&option2=true", $this->request->getQueryString());
        self::assertEquals("test", $this->request->get("option"));
        self::assertEquals(true, $this->request->get("option2"));
        $arr = ["option" => "test", "option2" => true, "aaaa" => "bbbb"];
        ksort($arr);
        $all = $this->request->getAll();
        ksort($all);
        self::assertTrue(is_array($all));

        self::assertSameSize($arr, $all);
        self::assertSame($arr, $all);
        self::assertFalse($this->request->isAJAX());
    }

    function test_methods2() {
        // URL: https://www.google.be:8443
        $_SERVER['HTTPS'] = "on";
        $_SERVER['HTTP_HOST'] = "www.google.be:8443";
        $_SERVER['SERVER_PORT'] = 8443;
        $_SERVER['REQUEST_URI'] = "/";
        $_SERVER['QUERY_STRING'] = "";
        $_SERVER['REQUEST_METHOD'] = "POST";
        $_GET = [];
        $_POST = [];
        $this->request = new RequestController();
        self::assertEquals("POST", $this->request->getMethod());
        self::assertTrue($this->request->isHttps());
        self::assertEquals("www.google.be", $this->request->getHost());
        self::assertEquals(8443, $this->request->getPort());
        self::assertEquals("/", $this->request->getPath());
        self::assertEquals("", $this->request->getFile());
        self::assertEquals("www.google.be:8443/", $this->request->getURL());
        self::assertEquals("https://www.google.be:8443/", $this->request->getURL(true));
        self::assertEquals("https://www.google.be:8443/", $this->request->getURL(true, true));
        self::assertEquals("", $this->request->getQueryString());
        self::assertTrue(is_array($this->request->getAll()));
        self::assertEmpty($this->request->getAll());
        self::assertFalse($this->request->isAJAX());
    }
}

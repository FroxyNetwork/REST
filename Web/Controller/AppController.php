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

namespace Web\Controller;

use Web\Core\Core;

abstract class AppController {

    /**
     * @var Core The Core
     */
    public $core;

    /**
     * @var RequestController The Request Controller
     */
    public $request;

    /**
     * @var ResponseController The Response Controller
     */
    public $response;

    public function __construct() {
    }

    public function get($param) {
        $this->response->notImplemented();
    }

    public function post($param) {
        $this->response->notImplemented();
    }

    public function put($param) {
        $this->response->notImplemented();
    }

    public function delete($param) {
        $this->response->notImplemented();
    }

    public function head($param) {
        $this->response->notImplemented();
    }

    public final function options($param) {
        $this->response->void();
    }

    // Autre chose
    public function other($param) {
        $this->response->notImplemented();
    }

    /**
     * @return array A list of implemented methods
     */
    public abstract function implementedMethods();
}
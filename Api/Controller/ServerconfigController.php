<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 FroxyNetwork
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

namespace Api\Controller;

use Api\Model\Scope;
use OAuth2\Request;
use Web\Controller\AppController;
use Web\Controller\ResponseController;
use Web\Core\Core;
use Web\Core\Error;

class ServerconfigController extends AppController {

    public function __construct(Core $core) {
        parent::__construct($core);
    }

    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_CONFIG)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        /**
         * @var ServerConfig $serverConfig
         */
        $serverConfig = $this->serverConfig;
        if ($this->core->startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln >= 1) {
            $type = $param;
            preg_match('/[a-zA-Z0-9_]+/', $type, $matches);
            if (!isset($matches) || !is_array($matches) || count($matches) != 1 || $matches[0] != $type) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TYPE_INVALID);
                return;
            }

            $parsedJson = $serverConfig->getParsedJson();
            if (!$parsedJson) {
                // Json error
                $this->response->error($serverConfig->getErrorType()[0], $serverConfig->getErrorType()[1]);
                return;
            }
            if (!$serverConfig->exist($param)) {
                // Json error
                $this->response->error(ResponseController::ERROR_NOTFOUND, Error::SERVER_TYPE_NOT_FOUND);
                return;
            }
            $this->response->ok(["types" => [$serverConfig->get($param)]]);
        } else {
            // Return servers.json file
            $parsedJson = $serverConfig->getParsedJson();
            if (!$parsedJson) {
                // Json error
                $this->response->error($serverConfig->getErrorType()[0], $serverConfig->getErrorType()[1]);
                return;
            }
            $this->response->ok($parsedJson);
        }
    }

    public function implementedMethods() {
        return ["GET"];
    }
}
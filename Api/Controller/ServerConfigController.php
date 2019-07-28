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
 */

namespace Api\Controller;

use OAuth2\Server;
use Web\Controller\AppController;
use Web\Controller\ResponseController;
use Web\Core\Core;
use Web\Core\Error;

class ServerConfigController extends AppController {

    public function get($param) {
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln >= 1) {

        } else {
            // Return servers.json file
            $json = file_get_contents(API_DIR.DS."Config".DS."servers.json");
            $parsedJson = json_decode($json);
            if (!$parsedJson || json_last_error() != JSON_ERROR_NONE) {
                // Json error
                $this->response->error(ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON);
                exit;
            }
            $this->response->ok($parsedJson);
        }
    }

    public function implementedMethods() {
        return ["GET"];
    }
}
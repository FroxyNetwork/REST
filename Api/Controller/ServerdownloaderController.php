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

namespace Api\Controller;

use Api\Model\Scope;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Controller\ResponseController;
use Web\Core\Core;
use Web\Core\Error;

class ServerdownloaderController extends AppController {

    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_DOWNLOAD)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TYPE_INVALID);
            return;
        }
        $type = $param;
        preg_match('/[a-zA-Z0-9_]+/', $type, $matches);
        if (!isset($matches) || !is_array($matches) || count($matches) != 1 || $matches[0] != $type) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TYPE_INVALID);
            return;
        }
        // ok
        /**
         * @var ServerConfig $serverConfig
         */
        $serverConfig = $this->serverConfig;
        if (!$serverConfig->getParsedJson()) {
            // Json error
            $this->response->error($serverConfig->getErrorType()[0], $serverConfig->getErrorType()[1]);
            exit;
        }
        if (!$serverConfig->exist($param)) {
            // Json error
            $this->response->error(ResponseController::ERROR_NOTFOUND, Error::SERVER_TYPE_NOT_FOUND);
            exit;
        }
        $copyType = $serverConfig->get($type);
        $id = $copyType['id'];
        $path = API_DIR.DS."Config".DS."Servers".DS.$id.".zip";
        if (!file_exists($path)) {
            $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_TYPE_FILE_NOT_FOUND);
            return;
        }
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"".$id.".zip"."\"");
        readfile($path);
    }

    public function implementedMethods() {
        return ["GET"];
    }
}
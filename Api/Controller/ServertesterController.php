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

use Api\Controller\DatasourceController\OAuth2DataController;
use Api\Controller\DatasourceController\ServerDataController;
use Api\Model\Scope;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Core\Core;
use Web\Core\Error;

class ServertesterController extends AppController {

    /**
     * @var ServerDataController
     */
    private $serverDataController;

    public function __construct() {
        parent::__construct();
        $this->serverDataController = Core::getDataController("Server");
    }

    /**
     * $param = a2b... (The ObjectId)
     * $_GET['client_id'] = CLIENT_KOTH_123... (The client_id)
     * $_GET['token'] = abc... (The token)
     */
    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;

        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::WEBSOCKET_SERVER_CHECK)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        $id = $param;

        // Invalid ObjectId
        if (!ctype_xdigit($id)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }

        // Invalid client_id
        if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        $explode = explode("_", $_GET['client_id']);
        // Doesn't start with "CLIENT" and doesn't have at least 2 underscores
        if (count($explode) < 2 || $explode[0] != "CLIENT") {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }

        // Invalid token
        if (!isset($_GET['token']) || empty($_GET['token'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        if (!ctype_xdigit($_GET['token'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        // Search server
        /**
         * @var OAuth2DataController $oauth_storage
         */
        $oauth_storage = $this->oauth_storage;
        $client = $oauth_storage->getAccessToken($_GET['token']);
        if (!$client) {
            $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_TESTER_INVALID);
            return;
        }
        // Not same object_id
        if ($client['user_id']->__toString() != $id) {
            $this->response->error($this->response::ERROR_UNAUTHORIZED, Error::SERVER_TESTER_INVALID);
            return;
        }
        // Not same client_id
        if ($client['client_id'] != $_GET['client_id']) {
            $this->response->error($this->response::ERROR_UNAUTHORIZED, Error::SERVER_TESTER_INVALID);
            return;
        }
        // Is expired ?
        $expireTime = $client['expires'];
        if (time() > $expireTime) {
            $this->response->error($this->response::ERROR_UNAUTHORIZED, Error::SERVER_TESTER_INVALID);
            return;
        }

        // Does the client have the "websocket_connection" scope
        $scopeSplit = explode(" ", $client['scope']);
        if (!$scopeSplit || count($scopeSplit) <= 0) {
            // Permission
            $this->response->ok(["ok" => false]);
            return;
        }
        $ok = false;
        foreach ($scopeSplit as $c)
            if ($c == "websocket_connection") {
                $ok = true;
                break;
            }
        $this->response->ok(["ok" => $ok]);
    }

    public function implementedMethods() {
        return ["GET"];
    }
}
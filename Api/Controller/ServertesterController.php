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

use Api\Controller\DatasourceController\ServerDataController;
use Api\Controller\DatasourceController\ServertesterDataController;
use Api\Model\Scope;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Core\Error;

class ServertesterController extends AppController {

    /**
     * @var ServerDataController
     */
    private $serverDataController;

    /**
     * @var ServertesterDataController
     */
    private $servertesterDataController;

    public function __construct() {
        parent::__construct();
        $this->serverDataController = $this->core->getDataController("Server");
        $this->servertesterDataController = $this->core->getDataController("Servertester");
    }

    /**
     * $param = a2b... (The id)
     * $_GET['client_id'] = CLIENT_KOTH_123... (The client_id)
     * $_GET['token'] = abc... (The token returned previously by the server)
     */
    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;

        // Test if there is an id or not
        if ($this->core->startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        // If there is not id, returns a Bad Request error
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        $id = $param;

        // Invalid token
        if (!isset($_GET['token']) || empty($_GET['token'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        if (!ctype_xdigit($_GET['token'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }
        $token = $_GET['token'];

        // VPS checker
        // Check if it's the CoreManager
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVERTESTER_CHECK)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }

        $savedToken = $this->servertesterDataController->get($id);
        if (!$savedToken) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }

        // Check if token is the same
        if ($token != $savedToken['token']) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TESTER_INVALID);
            return;
        }

        // Delete the token
        $this->servertesterDataController->delete($id);

        // Returns
        $this->response->ok(["ok" => true]);
    }

    public function post($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        $accessTokenData = $oauth->getAccessTokenData(Request::createFromGlobals(), null);
        if (!$accessTokenData || !isset($accessTokenData['scope']) || !$accessTokenData['scope'] || !$oauth->getScopeUtil()->checkScope(Scope::SERVERTESTER_CREATE, $accessTokenData['scope'])) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        // Generate token
        $token = $this->core->generateAuthorizationCode(32);
        // Save token
        if (!$this->servertesterDataController->create($accessTokenData['client_id'], $token)) {
            // Unknown error
            $this->response->error($this->response::SERVER_INTERNAL, Error::SERVER_TESTER_UNKNOWN);
            return;
        }

        // Send the token
        $this->response->ok([
            "id" => $accessTokenData['client_id'],
            "token" => $token
        ]);
    }


    public function implementedMethods() {
        return ["GET", "POST"];
    }
}
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
use Api\Model\ServerStatus;
use http\Env\Response;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Core\Core;
use Web\Core\Error;

class ServerController extends AppController {

    /**
     * @var ServerDataController
     */
    private $serverDataController;

    public function __construct() {
        parent::__construct();
        $this->serverDataController = Core::getDataController("Server");
    }

    /**
     * @param $param
     * $_GET['type'] = 1 for servers, 2 for bungees, 3 for all
     */
    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln >= 1) {
            // Search server
            if (!ctype_xdigit($param)) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ID_INVALID);
                return;
            }
            $server = $this->serverDataController->getServer($param);
            if (!$server) {
                // Server not found
                $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_NOT_FOUND);
                return;
            }
            $showMore = $oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_SHOW_MORE);
            $response = [
                "id" => $server['id'],
                "name" => $server['name'],
                "type" => $server['type'],
                "status" => $server['status'],
                "creationTime" => Core::formatDate($server['creation_time'])
            ];
            if (isset($server['end_time']) && !is_null($server['end_time']))
                $response['endTime'] = Core::formatDate($server['end_time']);
            if ($showMore) {
                $response['vps'] = $server['vps'];
                $response['port'] = $server['port'];
            }
            $this->response->ok($response);
        } else {
            $type = 3;
            if (isset($_GET['type']) && !empty($_GET['type'])) {
                if ($_GET['type'] == 1)
                    $type = 1;
                else if ($_GET['type'] == 2)
                    $type = 2;
            }
            // Search all opened server
            $servers = $this->serverDataController->getOpenedServers($type);
            $data = [];
            $data['size'] = count($servers);
            $data['servers'] = [];
            $showMore = $oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_SHOW_MORE);
            foreach ($servers as $server) {
                $d = [
                    "id" => $server['id'],
                    "name" => $server['name'],
                    "type" => $server['type'],
                    "status" => $server['status'],
                    "creationTime" => Core::formatDate($server['creation_time'])
                ];
                if (isset($server['end_time']) && !is_null($server['end_time']))
                    $d["endTime"] = Core::formatDate($server['end_time']);
                if ($showMore) {
                    $response['vps'] = $server['vps'];
                    $response['port'] = $server['port'];
                }
                $data['servers'][] = $d;
            }
            $this->response->ok($data);
        }
    }

    /**
     * DATA:
     * {
        "name" => "koth_1",
        "type" => "KOTH",
        "port" => 20001
     * }
     * @param $param
     */
    public function post($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        $accessTokenData = $oauth->getAccessTokenData(Request::createFromGlobals(), null);
        if (is_null($accessTokenData) || !isset($accessTokenData['scope']) || !$accessTokenData['scope'] || !$oauth->getScopeUtil()->checkScope(Scope::SERVERS_MANAGER, $accessTokenData['scope'])) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        /**
         * @var ServerConfig $serverConfig
         */
        $serverConfig = $this->serverConfig;
        // TODO Check if the port is already used.
        $data = json_decode($this->request->readInput(), TRUE);
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        if (!is_array($data) || empty($data['name']) || empty($data['type']) || !isset($data['port'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        $name = $data['name'];
        $type = $data['type'];
        $port = $data['port'];
        $vps = $accessTokenData['client_id'];
        // Check values
        if (!is_string($name)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_NAME_INVALID);
            return;
        }
        if (strlen($name) > 16) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_NAME_LENGTH);
            return;
        }
        if (!is_string($type)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TYPE_INVALID);
            return;
        }
        if (strlen($type) > 16) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_TYPE_LENGTH);
            return;
        }
        if (!Core::isInteger($port)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_PORT_INVALID);
            return;
        }
        $port = intval($port);
        if ($port <= 0 || $port >= 65536) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_PORT_INVALID);
            return;
        }
        if (!$serverConfig->existVps($vps)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_VPS_INVALID);
            return;
        }
        $s = $this->serverDataController->createServer($name, $type, $port, $vps);
        if (!$s) {
            // Error
            $this->response->error($this->response::SERVER_INTERNAL, Error::GLOBAL_UNKNOWN_ERROR);
            return;
        }
        $secret = $this->generateAuthorizationCode(32);
        /**
         * @var $oauth2DataController OAuth2DataController
         */
        $oauth2DataController = $this->oauth_storage;
		$scope = "server_show_more player_show_more servers";
        if (!$oauth2DataController->createClient($s['id'], $secret, $scope, $s['id'])) {
            // Error, we delete the server created previously
            $this->serverDataController->deleteServer($s['id']);

            $this->response->error($this->response::SERVER_INTERNAL, Error::SERVER_SAVING);
            return;
        }
        // Ok, create
        $this->response->ok([
            "id" => $s['id'],
            "name" => $s['name'],
            "type" => $s['type'],
            "vps" => $s['vps'],
            "port" => $s['port'],
            "status" => $s['status'],
            "creationTime" => Core::formatDate($s['creation_time']),
            "auth" => [
                "client_id" => $s['id'],
                "client_secret" => $secret
            ]
        ], $this->response::SUCCESS_CREATED);
        return;
    }

    /**
     * DATA:
     * {
        "status" => "STARTED"
     * }
     * "status" peut être :
     * - WAITING
     * - STARTED
     * - ENDING
     * @param $param
     */
    public function put($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_STATUS_EDIT)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ID_INVALID);
            return;
        }
        $id = $param;
        $data = json_decode($this->request->readInput(),TRUE);
        // Check values
        if (!ctype_xdigit($id)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ID_INVALID);
            return;
        }
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        if (!is_array($data) || empty($data['status'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        $status = $data['status'];
        if (!is_string($status)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_STATUS_INVALID);
            return;
        }
        if ($status != ServerStatus::WAITING && $status != ServerStatus::STARTED && $status != ServerStatus::ENDING) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_STATUS_INVALID);
            return;
        }
        // On récupère l'ancien serveur
        $s = $this->serverDataController->getServer($id);
        if (!$s) {
            $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_NOT_FOUND);
            return;
        }
        // On teste si le status est bon
        if (!ServerStatus::isAfter($status, $s['status'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_STATUS_BEFORE, ["currentStatus" => $s['status']]);
            return;
        }
        // Tout est bon, on update les valeurs
        $s['status'] = $status;
        $s2 = $this->serverDataController->updateServer($s);
        if (!$s2) {
            $this->response->error($this->response::SERVER_INTERNAL, Error::GLOBAL_UNKNOWN_ERROR);
            return;
        }
        $this->response->ok([
            "id" => $s['id'],
            "name" => $s['name'],
            "type" => $s['type'],
            "vps" => $s['vps'],
            "port" => $s['port'],
            "status" => $s['status'],
            "creationTime" => Core::formatDate($s['creation_time'])
        ], $this->response::SUCCESS_OK);
    }

    public function delete($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVERS_MANAGER)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ID_INVALID);
            return;
        }
        $id = $param;
        // Check values
        if (!ctype_xdigit($id)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ID_INVALID);
            return;
        }
        // Check if the entry exists
        $s = $this->serverDataController->getServer($id);

        if (!$s) {
            $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_NOT_FOUND);
            return;
        }
        if ($s['status'] == ServerStatus::ENDED) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ACTUALLY_ENDED);
            return;
        }

        if ($s['status'] == ServerStatus::STARTING) {
            // If status is "STARTING", we'll delete it instead of closing it
            if (!$this->serverDataController->deleteServer($s['id'])) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_UNKNOWN_ERROR);
                return;
            }
            $this->response->ok();
            return;
        }

        // Update
        if (!$this->serverDataController->closeServer($id)) {
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_UNKNOWN_ERROR);
            return;
        }
        $this->response->ok();
    }

    public function implementedMethods() {
        return ["GET", "POST", "PUT", "DELETE"];
    }

    /**
     * @see https://github.com/bshaffer/oauth2-server-php/blob/master/src/OAuth2/ResponseType/AuthorizationCode.php#L84
     *
     * @param $ln int The size of the random data
     *
     * @return bool|string
     */
    function generateAuthorizationCode($ln) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $randomData = openssl_random_pseudo_bytes(64);
        } elseif (function_exists('random_bytes')) {
            $randomData = random_bytes(64);
        } elseif (function_exists('mcrypt_create_iv')) {
            $randomData = mcrypt_create_iv(64, MCRYPT_DEV_URANDOM);
        } elseif (@file_exists('/dev/urandom')) { // Get 64 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 64) . uniqid(mt_rand(), true);
        } else {
            $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
        }
        return substr(hash('sha512', $randomData), 0, $ln);
    }
}
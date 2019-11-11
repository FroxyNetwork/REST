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
            $port = -1;
            if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_SHOW_PORT))
                $port = $server['port'];
            $response = [
                "id" => $server['id'],
                "name" => $server['name'],
                "type" => $server['type'],
                "port" => $port,
                "status" => $server['status'],
                "creationTime" => Core::formatDate($server['creation_time'])
            ];
            if (isset($v['end_time']) && !is_null($server['end_time']))
                $response["endTime"] = Core::formatDate($server['end_time']);
            $this->response->ok($response);
        } else {
            // Search all opened server
            $servers = $this->serverDataController->getOpenedServers();
            $data = [];
            $data['size'] = count($servers);
            $data['servers'] = [];
            $showPort = $oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_SHOW_PORT);
            foreach ($servers as $server) {
                $d = [
                    "id" => $server['id'],
                    "name" => $server['name'],
                    "type" => $server['type'],
                    "port" => ($showPort) ? $server['port'] : -1,
                    "status" => $server['status'],
                    "creationTime" => Core::formatDate($server['creation_time'])
                ];
                if (isset($v['end_time']) && !is_null($server['end_time']))
                    $d["endTime"] = Core::formatDate($server['end_time']);
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
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_CREATE)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        // TODO Check if the port is already used.
        $data = json_decode($this->request->readInput(),TRUE);
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
        $s = $this->serverDataController->createServer($name, $type, $port);
        if (!$s) {
            // Error
            $this->response->error($this->response::SERVER_INTERNAL, Error::GLOBAL_UNKNOWN_ERROR);
            return;
        }
        $clientSecret = $this->generateClientSecret($s['type']);
        /**
         * @var $oauth2DataController OAuth2DataController
         */
        $oauth2DataController = $this->oauth_storage;
		$scope = "server_show_port player_show_realname player_show_ip websocket_connection";
        if (!$oauth2DataController->createClient($clientSecret[0], $clientSecret[1], $scope, $s['id'])) {
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
            "port" => $s['port'],
            "status" => $s['status'],
            "creationTime" => Core::formatDate($s['creation_time']),
            "auth" => [
                "client_id" => $clientSecret[0],
                "client_secret" => $clientSecret[1]
            ]
        ], $this->response::SUCCESS_CREATED);
        return;
    }

    /**
     * DATA:
     * {
        "status" => "STARTED"
     * }
     * Status peut être :
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
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_CREATE)) {
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
        // Check if it ends with "/id"
        if (Core::endsWith($id, "/id")) {
            // Check values
            $id = substr($id, 0, -3);

            // Check values
            if (!ctype_xdigit($id)) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_ID_INVALID);
                return;
            }
            if (empty($data)) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
                return;
            }
            if (!is_array($data) || empty($data['server']) || empty($data['id'])) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
                return;
            }
            $serverId = $data['server'];
            if (!is_string($serverId)) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_SERVER_INVALID);
                return;
            }
            // On vérifie si l'id du vps existe
            /**
             * @var ServerConfig $serverConfig
             */
            $serverConfig = $this->serverConfig;
            if (!$serverConfig->existVps($serverId)) {
                // Json error
                $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_SERVER_INVALID);
                exit;
            }

            $dockerId = $data['id'];
            if (!is_string($dockerId)) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::SERVER_SERVER_DOCKER_INVALID);
                return;
            }

            // On vérifie si le serveur existe
            $s = $this->serverDataController->getServer($id);
            if (!$s) {
                $this->response->error($this->response::ERROR_NOTFOUND, Error::SERVER_NOT_FOUND);
                return;
            }
            // On check si c'est déjà modifié
            if ($this->serverDataController->checkServerDocker($id)) {
                $this->response->error($this->response::ERROR_FORBIDDEN, Error::SERVER_SERVER_ALREADY_ID);
                return;
            }
            if ($this->serverDataController->updateServerDocker($id, $serverId, $dockerId)) {
                $this->response->ok();
                return;
            } else {
                // Erreur
                $this->response->error($this->response::SERVER_INTERNAL, Error::SERVER_SAVING);
            }
        } else {
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
                "port" => $s['port'],
                "status" => $s['status'],
                "creationTime" => Core::formatDate($s['creation_time'])
            ], $this->response::SUCCESS_OK);
            return;
        }
    }

    public function delete($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_CREATE)) {
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
     * Generate a client_id and a client_secret (Used for Java servers)
     *
     * @param $id string The id of the server
     * @return array Client and Secret
     */
    function generateClientSecret($id) {
        $client = "CLIENT_" . $id . "_" . $this->generateAuthorizationCode(32);
        $secret = "SECRET_" . $id . "_" . $this->generateAuthorizationCode(32);
        return [$client, $secret];
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
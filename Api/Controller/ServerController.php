<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 0ddlyoko
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

use Api\Controller\DatasourceController\OAuth2DataController;
use Api\Controller\DatasourceController\ServerDataController;
use Api\Model\Scope;
use Api\Model\ServerStatus;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Core\Core;

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
            if (!Core::isInteger($param)) {
                $this->response->error($this->response::ERROR_BAD_REQUEST, "Invalid id.");
                return;
            }
            $server = $this->serverDataController->getServer($param);
            if (!empty($GLOBALS['errorCode'])) {
                if ($GLOBALS['errorCode'] == ServerDataController::ERROR_NOT_FOUND) {
                    // Server not found
                    $this->response->error($this->response::ERROR_NOTFOUND, "Server not found");
                    return;
                }
                // Error
                $this->response->error($this->response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
                return;
            } else if ($server == null) {
                // Unknown error
                $this->response->error($this->response::ERROR_BAD_REQUEST, "Unknown error");
                return;
            }
            $port = -1;
            if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_SHOW_PORT))
                $port = $server->getPort();
            $this->response->ok([
                "id" => $server->getId(),
                "name" => $server->getName(),
                "port" => $port,
                "status" => $server->getStatus(),
                "creationTime" => Core::formatDate($server->getCreationTime())
            ]);
        } else {
            // Search all opened server
            $servers = $this->serverDataController->getOpenedServers();
            $data = [];
            $data['size'] = count($servers);
            $data['servers'] = [];
            $showPort = $oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_SHOW_PORT);
            foreach ($servers as $server)
                $data['servers'][] = [
                    "id" => $server->getId(),
                    "name" => $server->getName(),
                    "port" => ($showPort) ? $server->getPort() : -1,
                    "status" => $server->getStatus(),
                    "creationTime" => Core::formatDate($server->getCreationTime())
                ];
            $this->response->ok($data);
        }
    }

    /**
     * DATA:
     * {
        "name" => "game_1",
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
            $this->response->error($this->response::ERROR_FORBIDDEN, "You don't have the permission to create servers !");
            return;
        }
        // TODO Check if the port is already used.
        $data = json_decode($this->request->readInput(),TRUE);
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Data not found !");
            return;
        }
        if (!is_array($data) || empty($data['name']) || !isset($data['port'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Invalid data value !");
            return;
        }
        $name = $data['name'];
        $port = $data['port'];
        // Check values
        if (!is_string($name)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Name must be a string !");
            return;
        }
        if (strlen($name) > 16) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Name length must be between 1 and 16 !");
            return;
        }
        if (!Core::isInteger($port)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Port must be a correct number !");
            return;
        }
        $port = intval($port);
        if ($port <= 0 || $port >= 65536) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Port must be between 1 and 65535 !");
            return;
        }
        $s = $this->serverDataController->createServer($name, $port);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($s == null) {
            // Unknown error
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        $clientSecret = $this->generateClientSecret($s->getId());
        /**
         * @var $oauth2DataController OAuth2DataController
         */
        $oauth2DataController = $this->oauth_storage;
		$scope = "server_show_port player_show_realname player_show_ip";
        if (!$oauth2DataController->createClient($clientSecret[0], $clientSecret[1], $scope, $s->getId())) {
            // Error, we delete the server created previously
            $this->serverDataController->deleteServer($s->getId());

            $this->response->error($this->response::SERVER_INTERNAL, "Error while saving client_id and client_secret");
            return;
        }
        // Ok, create
        $this->response->ok([
            "id" => $s->getId(),
            "name" => $s->getName(),
            "port" => $s->getPort(),
            "status" => $s->getStatus(),
            "creationTime" => Core::formatDate($s->getCreationTime()),
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
            $this->response->error($this->response::ERROR_FORBIDDEN, "You don't have the permission to edit servers !");
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Id not found.");
            return;
        }
        $id = $param;
        $data = json_decode($this->request->readInput(),TRUE);
        // Check values
        if (!Core::isInteger($id)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Invalid id !");
            return;
        }
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Data not found !");
            return;
        }
        if (!is_array($data) || empty($data['status'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Invalid data value !");
            return;
        }
        $status = $data['status'];
        if (!is_string($status)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Status must be a string !");
            return;
        }
        if ($status != ServerStatus::WAITING && $status != ServerStatus::STARTED && $status != ServerStatus::ENDING) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Status must be '" . ServerStatus::WAITING . "', '" . ServerStatus::STARTED . "' or '" . ServerStatus::ENDING . "' !");
            return;
        }
        // On récupère l'ancien serveur
        $s = $this->serverDataController->getServer($id);
        if (!$s) {
            $this->response->error($this->response::ERROR_NOTFOUND, "Server not found !");
            return;
        }
        // On teste si le status est bon
        if (!ServerStatus::isAfter($status, $s->getStatus())) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Invalid status, current is " . $s->getStatus() . " !");
            return;
        }
        // Tout est bon, on update les valeurs
        $s->setStatus($status);
        unset($GLOBALS['errorCode']);
        unset($GLOBALS['error']);
        $s2 = $this->serverDataController->updateServer($s);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($s2 == null) {
            // Unknown error
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        $this->response->ok([
            "id" => $s->getId(),
            "name" => $s->getName(),
            "port" => $s->getPort(),
            "status" => $s->getStatus(),
            "creationTime" => Core::formatDate($s->getCreationTime())
        ], $this->response::SUCCESS_CREATED);
        return;
    }

    public function delete($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::SERVER_CREATE)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, "You don't have the permission to delete servers !");
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln <= 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Id not found.");
            return;
        }
        $id = $param;
        // Check values
        if (!Core::isInteger($id)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Invalid id !");
            return;
        }
        // Check if the entry exists
        $s = $this->serverDataController->getServer($id);

        if (!$s) {
            $this->response->error($this->response::ERROR_NOTFOUND, "Server not found !");
            return;
        }
        if ($s->getStatus() == ServerStatus::ENDED) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, "This server is already ended !");
            return;
        }

        // Update
        if (!$this->serverDataController->closeServer($id)) {
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        }
        $this->response->ok([]);
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
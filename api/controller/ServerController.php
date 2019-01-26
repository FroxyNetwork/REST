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

use Api\Controller\DatasourceController\ServerDataController;
use Web\Controller\AppController;
use Web\Core\Core;
use Web\Core\Response;

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
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln >= 1) {
            // Search server
            if (!Core::isInteger($param)) {
                Response::error(Response::ERROR_BAD_REQUEST, "Invalid id.");
                return;
            }
            $server = $this->serverDataController->getServer($param);
            if (!empty($GLOBALS['errorCode'])) {
                if ($GLOBALS['errorCode'] == ServerDataController::ERROR_NOT_FOUND) {
                    // Server not found
                    Response::error(Response::ERROR_NOTFOUND, "Server not found");
                    return;
                }
                // Error
                Response::error(Response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
                return;
            } else if ($server == null) {
                // Unknown error
                Response::error(Response::ERROR_BAD_REQUEST, "Unknown error");
                return;
            }
            Response::ok([
                "id" => $server->getId(),
                "name" => $server->getName(),
                "port" => $server->getPort(),// TODO Allow only for special ranks
                "status" => $server->getStatus(),
                "creationTime" => Core::formatDate($server->getCreationTime())
            ]);
        } else {
            // Search all opened server
            $servers = $this->serverDataController->getOpenedServers();
            $data = [];
            $data['size'] = count($servers);
            $data['servers'] = [];
            foreach ($servers as $server)
                $data['servers'][] = [
                    "id" => $server->getId(),
                    "name" => $server->getName(),
                    "port" => $server->getPort(),// TODO Allow only for special ranks
                    "status" => $server->getStatus(),
                    "creationTime" => Core::formatDate($server->getCreationTime())
                ];
            Response::ok($data);
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
        // TODO Check if the request come from the WebSocket
        // TODO Check if the port is already used.
        $data = json_decode(file_get_contents('php://input'),TRUE);
        if (empty($data)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Data not found !");
            return;
        }
        if (!is_array($data) || empty($data['name']) || !isset($data['port'])) {
            Response::error(Response::ERROR_BAD_REQUEST, "Invalid data value !");
            return;
        }
        $name = $data['name'];
        $port = $data['port'];
        // Check values
        if (!is_string($name)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Name must be a string !");
            return;
        }
        if (strlen($name) > 16) {
            Response::error(Response::ERROR_BAD_REQUEST, "Name length must be between 1 and 16 !");
            return;
        }
        if (!Core::isInteger($port)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Port must be a correct number !");
            return;
        }
        $port = intval($port);
        if ($port <= 0 || $port >= 65536) {
            Response::error(Response::ERROR_BAD_REQUEST, "Port must be between 1 and 65535 !");
            return;
        }
        $s = $this->serverDataController->createServer($name, $port);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            Response::error(Response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($s == null) {
            // Unknown error
            Response::error(Response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        Response::ok([
            "id" => $s->getId(),
            "name" => $s->getName(),
            "port" => $s->getPort(), // TODO Allow only for special ranks
            "status" => $s->getStatus(),
            "creationTime" => Core::formatDate($s->getCreationTime())
        ], Response::SUCCESS_CREATED);
        return;
    }
}
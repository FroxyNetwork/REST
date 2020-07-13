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
 *
 * @author 0ddlyoko
 * @author Sloyni
 */

namespace Api\Controller;

use Api\Controller\DatasourceController\PlayerDataController;
use Api\Controller\DatasourceController\ServerDataController;
use Api\Model\Scope;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Core\Core;
use Web\Core\Error;

class PlayerController extends AppController {

    /**
     * @var PlayerDataController
     */
    private $playerDataController;

    /**
     * @var ServerDataController
     */
    private $serverDataController;

    public function __construct(Core $core) {
        parent::__construct($core);
        $this->playerDataController = $core->getDataController("Player");
        $this->serverDataController = $core->getDataController("Server");
    }

    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if ($this->core->startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        $player = false;
        if ($ln == 36) {
            // UUID
            $player = $this->playerDataController->getUserByUUID($param);
        } else if ($ln >= 1 && $ln <= 20) {
            // Name
            $player = $this->playerDataController->getUserByPseudo($param);
        } else {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_DATA_LENGTH);
            return;
        }
        if (!$player) {
            // Player not found
            $this->response->error($this->response::ERROR_NOTFOUND, Error::PLAYER_NOT_FOUND);
            return;
        }
        $return = [
            "uuid" => $player['uuid'],
            "nickname" => $player['nickname'],
            "coins" => $player['coins'],
            "level" => $player['level'],
            "exp" => $player['exp'],
            "firstLogin" => $this->core->formatDate($player['first_login']),
            "lastLogin" => $this->core->formatDate($player['last_login']),
            "lang" => $player['lang']
        ];
        if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_SHOW_MORE)) {
            $return['displayName'] = $player['display_name'];
            $return['ip'] = $player['ip'];
            if (isset($player['server']))
                $return['server'] = $player['server'];
        }
        $this->response->ok($return);
    }

    /**
     * DATA:
     * {
        "uuid" => "86173d9f-f7f4-4965-8e9d-f37783bf6fa7",
        "nickname" => "0ddlyoko",
        "ip" => "127.0.0.1"
     * }
     * Pas plus, les données par défauts vont être gérées par la bdd
     * @param $param
     */
    public function post($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_CREATE)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        $data = json_decode($this->request->readInput(),TRUE);
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        if (!is_array($data) || empty($data['uuid']) || empty($data['nickname']) || empty($data['ip'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        $uuid = $data['uuid'];
        $nickname = $data['nickname'];
        $ip = $data['ip'];
        // Check values
        if (strlen($uuid) != 36) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_UUID_LENGTH);
            return;
        }
        if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_IP_FORMAT);
            return;
        }
        if (strlen($nickname) < 1 || strlen($nickname) > 20) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_PSEUDO_LENGTH);
            return;
        }
        if (strlen($ip) < 7 || strlen($ip) > 15) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_IP_LENGTH);
            return;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_IP_FORMAT);
            return;
        }
        // uuid already exist ?
        if ($this->playerDataController->getUserByUUID($uuid)) {
            $this->response->error($this->response::ERROR_CONFLICT, Error::PLAYER_UUID_EXISTS);
            return;
        }
        // player name already exist ?
        if ($this->playerDataController->getUserByPseudo($nickname)) {
            $this->response->error($this->response::ERROR_CONFLICT, Error::PLAYER_PSEUDO_EXISTS);
            return;
        }
        $p = $this->playerDataController->createUser($uuid, $nickname, $ip);
        if (!$p) {
            // Unknown error
            $this->response->error($this->response::SERVER_INTERNAL, Error::GLOBAL_UNKNOWN_ERROR);
            return;
        }
        $this->response->ok([
            "uuid" => $p['uuid'],
            "nickname" => $p['nickname'],
            "displayName" => $p['display_name'],
            "coins" => $p['coins'],
            "level" => $p['level'],
            "exp" => $p['exp'],
            "firstLogin" => $this->core->formatDate($p['first_login']),
            "lastLogin" => $this->core->formatDate($p['last_login']),
            "ip" => $p['ip'],
            "lang" => $p['lang']
        ], $this->response::SUCCESS_CREATED);
        return;
    }

    /**
     * DATA:
     * {
        "nickname" => "0ddlyoko",
        "displayName" => "0ddlyoko",
        "coins" => 5,
        "level" => 2,
        "exp" => 12,
        "lastLogin" => "...",
        "ip" => "127.0.0.1",
        "lang" => "fr_FR",
        "server" => "62ec3f29d48a15ce2de9106981945d17082935ae"
     * }
     * // TODO Retirer "nickname" (Trouver un autre moyen pour changer de pseudo (Genre une autre url + vérif par mail etc ==> Mail ?))
     * // TODO Retirer "coins", "level", "exp" (On va gérer ça par un autre moyen)
     *
     * Update specific user
     * @param $param
     */
    public function put($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_CREATE)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, Error::GLOBAL_NO_PERMISSION);
            return;
        }
        if ($this->core->startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln != 36) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_UUID_LENGTH);
            return;
        }
        $data = json_decode($this->request->readInput(),TRUE);
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        if (!is_array($data) || empty($data['nickname']) || empty($data['displayName']) || empty($data['lastLogin']) || empty($data['ip']) || empty($data['lang'])) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_DATA_INVALID);
            return;
        }
        $uuid = $param;
        $nickname = $data['nickname'];
        $displayName = $data['displayName'];
        $coins = $data['coins'];
        $level = $data['level'];
        $exp = $data['exp'];
        $lastLogin = $data['lastLogin'];
        $ip = $data['ip'];
        $lang = $data['lang'];
        $newServerId = isset($data['server']) ? $data['server'] : null;
        if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_UUID_FORMAT);
            return;
        }
        if (strlen($nickname) < 1 || strlen($nickname) > 20) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_PSEUDO_LENGTH);
            return;
        }
        if (strlen($displayName) < 1 || strlen($displayName) > 20) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_DISPLAYNAME_LENGTH);
            return;
        }
        if (!is_int($coins) || $coins < 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_COINS_POSITIVE);
            return;
        }
        if (!is_int($level) || $level < 0) {
            // TODO Autoriser la suppression des niveaux (Genre on peut "acheter" des améliorations avec des niveaux, ...)
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_LEVEL_POSITIVE);
            return;
        }
        if (!is_int($exp) || $exp < 0) {
            // TODO Idem que "level"
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_EXP_POSITIVE);
            return;
        }
        // lastLogin
        try {
            $lastLogin = new \DateTime($lastLogin);
        } catch (\Exception $ex) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_TIME_FORMAT);
            return;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_IP_FORMAT);
            return;
        }
        // TODO Vérifier si la langue est correcte
        // On récupère l'ancien joueur
        $p = $this->playerDataController->getUserByUUID($uuid);
        if (!$p) {
            $this->response->error($this->response::ERROR_NOTFOUND, Error::PLAYER_NOT_FOUND);
            return;
        }
        // On teste si lastLogin est bien égal ou plus petit que le lastLogin sauvegardé
        if ($lastLogin <= $p['last_login']) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_LASTLOGIN_GREATER);
            return;
        }
        // Check if server is string type
        if (isset($newServerId) && !is_string($newServerId)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_SERVER_INVALID);
            return;
        }
        $p['nickname'] = $nickname;
        $p['display_name'] = $displayName;
        $p['coins'] = $coins;
        $p['level'] = $level;
        $p['exp'] = $exp;
        $p['last_login'] = $lastLogin;
        $p['ip'] = $ip;
        $p['lang'] = $lang;
        // Check if it's a different server
        if (isset($newServerId) && (!isset($p['server']) || $p['server']['id'] != $newServerId)) {
            // Update new Server Id
            // Get server
            $server = $this->serverDataController->getServer($newServerId);
            // Check if server is found
            if (!$server) {
                // Server not found
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_SERVER_NOT_FOUND);
                return;
            }
            // Check if server is opened
            // 'STARTING', 'WAITING', 'STARTED', 'ENDING'
            if ($server['status'] != 'STARTING' && $server['status'] != 'WAITING' && $server['status'] != 'STARTED' && $server['status'] != 'ENDING') {
                $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_SERVER_NOT_OPENED);
                return;
            }
            $p['server'] = [
                'id' => $server['id'],
                'name' => $server['name'],
                'type' => $server['type']
            ];
            if (isset($server['docker'])) {
                // Save docker
                $p['server']['docker'] = [
                    'server' => $server['docker']['server'],
                    'id' => $server['docker']['id']
                ];
            }
        }
        if (!isset($newServerId))
            // Unset server to clear it if no server has been specified in the request
            unset($p['server']);
        // Tout est bon, on update les valeurs
        $p2 = $this->playerDataController->updateUser($p);
        if ($p2 == null) {
            // Unknown error
            $this->response->error($this->response::SERVER_INTERNAL, Error::GLOBAL_UNKNOWN_ERROR);
            return;
        }
        $return = [
            "uuid" => $p2['uuid'],
            "nickname" => $p2['nickname'],
            "displayName" => $p2['display_name'],
            "coins" => $p2['coins'],
            "level" => $p2['level'],
            "exp" => $p2['exp'],
            "firstLogin" => $this->core->formatDate($p2['first_login']),
            "lastLogin" => $this->core->formatDate($p2['last_login']),
            "ip" => $p2['ip'],
            "lang" => $p2['lang']
        ];
        if (isset($p2['server']))
            $return['server'] = $p2['server'];
        $this->response->ok($return, $this->response::SUCCESS_OK);
        return;
    }

    public function implementedMethods() {
        return ["GET", "POST", "PUT"];
    }
}
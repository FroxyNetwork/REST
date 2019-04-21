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
 * @author Sloyni
 */

namespace Api\Controller;

use Api\Controller\DatasourceController\PlayerDataController;
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

    public function __construct() {
        parent::__construct();
        $this->playerDataController = Core::getDataController("Player");
    }

    public function get($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        $player = null;
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
        if (!empty($GLOBALS['errorCode'])) {
            if ($GLOBALS['errorCode'] == PlayerDataController::ERROR_NOT_FOUND) {
                // Player not found
                $this->response->error($this->response::ERROR_NOTFOUND, Error::PLAYER_NOT_FOUND);
                return;
            }
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_ERROR, ["errorCode" => $GLOBALS['errorCode'], "error" => $GLOBALS['error']]);
            return;
        } else if ($player == null) {
            // Unknown error
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_UNKNOWN);
            return;
        }
        $displayName = "<HIDDEN>";
        if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_SHOW_REALNAME))
            $displayName = $player->getDisplayName();
        $ip = "<HIDDEN>";
        if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_SHOW_IP))
            $ip = $player->getIp();
        $this->response->ok([
            "uuid" => $player->getUuid(),
            "nickname" => $player->getNickname(),
            "displayName" => $displayName,
            "coins" => $player->getCoins(),
            "level" => $player->getLevel(),
            "exp" => $player->getExp(),
            "firstLogin" => Core::formatDate($player->getFirstLogin()),
            "lastLogin" => Core::formatDate($player->getLastLogin()),
            "ip" => $ip,
            "lang" => $player->getLang()
        ]);
    }

    /**
     * DATA:
     * {
        "uuid" => "86173d9f-f7f4-4965-8e9d-f37783bf6fa7",
        "nickname" => "0ddlyoko",
        "ip" => "127.0.0.1"
     * }
     * Pas plus, les données par défauts vont être gérés par la bdd
     * @param $param
     */
    public function post($param) {
        /**
         * @var Server $oauth
         */
        $oauth = $this->oauth;
        if (!$oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_CREATE)) {
            // Invalid perm
            $this->response->error($this->response::ERROR_FORBIDDEN, ERROR::GLOBAL_NO_PERMISSION);
            return;
        }
        $data = json_decode($this->request->readInput(),TRUE);
        if (empty($data)) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, ERROR::GLOBAL_DATA_INVALID);
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
        unset($GLOBALS['error']);
        unset($GLOBALS['errorCode']);
        $p = $this->playerDataController->createUser($uuid, $nickname, $ip);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_ERROR, ["errorCode" => $GLOBALS['errorCode'], "error" => $GLOBALS['error']]);
            return;
        } else if ($p == null) {
            // Unknown error
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_UNKNOWN);
            return;
        }
        $this->response->ok([
            "uuid" => $p->getUuid(),
            "nickname" => $p->getNickname(),
            "displayName" => $p->getDisplayName(),
            "coins" => $p->getCoins(),
            "level" => $p->getLevel(),
            "exp" => $p->getExp(),
            "firstLogin" => Core::formatDate($p->getFirstLogin()),
            "lastLogin" => Core::formatDate($p->getLastLogin()),
            "ip" => $p->getIp(),
            "lang" => $p->getLang()
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
        "lang" => "fr_FR"
     * }
     * // TODO Retirer "nickname" (Trouver un autre moyen pour changer de pseudo (Genre une autre url + vérif par mail etc ==> Mail ?))
     * // TODO Retirer "coins", "level", "exp" (On va gérer ça par un autre moyen)
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
        if (Core::startsWith($param, "/"))
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
        if ($coins < 0) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_COINS_POSITIVE);
            return;
        }
        if ($level < 0) {
            // TODO Autoriser la suppression des niveaux (Genre on peut "acheter" des améliorations avec des niveaux, ...)
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_LEVEL_POSITIVE);
            return;
        }
        if ($exp < 0) {
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
        if ($lastLogin <= $p->getLastLogin()) {
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::PLAYER_LASTLOGIN_GREATER);
            return;
        }
        // Tout est bon, on update les valeurs
        $p->setNickname($nickname);
        $p->setDisplayName($displayName);
        $p->setCoins($coins);
        $p->setLevel($level);
        $p->setExp($exp);
        $p->setLastLogin($lastLogin);
        $p->setIp($ip);
        $p->setLevel($level);
        $p->setLang($lang);
        $p2 = $this->playerDataController->updateUser($p);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            $this->response->error($this->response::ERROR_BAD_REQUEST, Error::GLOBAL_ERROR, ["errorCode" => $GLOBALS['errorCode'], "error" => $GLOBALS['error']]);
            return;
        } else if ($p2 == null) {
            // Unknown error
            $this->response->error($this->response::ERROR_BAD_REQUEST, ERROR::GLOBAL_UNKNOWN);
            return;
        }
        $this->response->ok([
            "uuid" => $p2->getUuid(),
            "nickname" => $p2->getNickname(),
            "displayName" => $p2->getDisplayName(),
            "coins" => $p2->getCoins(),
            "level" => $p2->getLevel(),
            "exp" => $p2->getExp(),
            "firstLogin" => Core::formatDate($p2->getFirstLogin()),
            "lastLogin" => Core::formatDate($p2->getLastLogin()),
            "ip" => $p2->getIp(),
            "lang" => $p2->getLang()
        ], $this->response::SUCCESS_OK);
        return;
    }
}
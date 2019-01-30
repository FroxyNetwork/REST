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

use Api\Controller\DatasourceController\PlayerDataController;
use Api\Model\Scope;
use OAuth2\Request;
use OAuth2\Server;
use Web\Controller\AppController;
use Web\Core\Core;
use Web\Core\Response;

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
            Response::error(Response::ERROR_BAD_REQUEST, "The length of the search must be between 1 and 20, or equals to 36.");
            return;
        }
        if (!empty($GLOBALS['errorCode'])) {
            if ($GLOBALS['errorCode'] == PlayerDataController::ERROR_NOT_FOUND) {
                // Player not found
                Response::error(Response::ERROR_NOTFOUND, "Player not found");
                return;
            }
            // Error
            Response::error(Response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($player == null) {
            // Unknown error
            Response::error(Response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        $displayName = "<HIDDEN>";
        if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_SHOW_REALNAME))
            $displayName = $player->getDisplayName();
        $ip = "<HIDDEN>";
        if ($oauth->verifyResourceRequest(Request::createFromGlobals(), null, Scope::PLAYER_SHOW_IP))
            $ip = $player->getIp();
        Response::ok([
            "uuid" => $player->getUuid(),
            "pseudo" => $player->getPseudo(),
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
        "pseudo" => "0ddlyoko",
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
            Response::error(Response::ERROR_FORBIDDEN, "You don't have the permission to create players !");
            return;
        }
        $data = json_decode(file_get_contents('php://input'),TRUE);
        if (empty($data)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Data not found !");
            return;
        }
        if (!is_array($data) || empty($data['uuid']) || empty($data['pseudo']) || empty($data['ip'])) {
            Response::error(Response::ERROR_BAD_REQUEST, "Invalid data value !");
            return;
        }
        $uuid = $data['uuid'];
        $pseudo = $data['pseudo'];
        $ip = $data['ip'];
        // Check values
        if (strlen($uuid) != 36) {
            Response::error(Response::ERROR_BAD_REQUEST, "UUID length must be 36 !");
            return;
        }
        if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Bad UUID format !");
            return;
        }
        if (strlen($pseudo) < 1 || strlen($pseudo) > 20) {
            Response::error(Response::ERROR_BAD_REQUEST, "Pseudo length must be between 1 and 20 !");
            return;
        }
        if (strlen($ip) < 7 || strlen($ip) > 15) {
            Response::error(Response::ERROR_BAD_REQUEST, "Ip length must be between 7 and 15 !");
            return;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Bad ip format !");
            return;
        }
        // uuid already exist ?
        if ($this->playerDataController->getUserByUUID($uuid)) {
            Response::error(Response::ERROR_CONFLICT, "UUID already exist !");
            return;
        }
        // player name already exist ?
        if ($this->playerDataController->getUserByPseudo($pseudo)) {
            Response::error(Response::ERROR_CONFLICT, "Pseudo already exist !");
            return;
        }
        unset($GLOBALS['error']);
        unset($GLOBALS['errorCode']);
        $p = $this->playerDataController->createUser($uuid, $pseudo, $ip);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            Response::error(Response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($p == null) {
            // Unknown error
            Response::error(Response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        Response::ok([
            "uuid" => $p->getUuid(),
            "pseudo" => $p->getPseudo(),
            "displayName" => $p->getDisplayName(),
            "coins" => $p->getCoins(),
            "level" => $p->getLevel(),
            "exp" => $p->getExp(),
            "firstLogin" => Core::formatDate($p->getFirstLogin()),
            "lastLogin" => Core::formatDate($p->getLastLogin()),
            "ip" => $p->getIp(),
            "lang" => $p->getLang()
        ], Response::SUCCESS_CREATED);
        return;
    }

    /**
     * DATA:
     * {
        "pseudo" => "0ddlyoko",
        "displayName" => "0ddlyoko",
        "coins" => 5,
        "level" => 2,
        "exp" => 12,
        "lastLogin" => "...",
        "ip" => "127.0.0.1",
        "lang" => "fr_FR"
     * }
     * // TODO Retirer "pseudo" (Trouver un autre moyen pour changer de pseudo (Genre une autre url + vérif par mail etc ==> Mail ?))
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
            Response::error(Response::ERROR_FORBIDDEN, "You don't have the permission to edit players !");
            return;
        }
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        if ($ln != 36) {
            Response::error(Response::ERROR_BAD_REQUEST, "Invalid UUID length in url, it must be equals to 36.");
            return;
        }
        $data = json_decode(file_get_contents('php://input'),TRUE);
        if (empty($data)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Data not found !");
            return;
        }
        if (!is_array($data) || empty($data['pseudo']) || empty($data['displayName']) || empty($data['lastLogin']) || empty($data['ip']) || empty($data['lang'])) {
            Response::error(Response::ERROR_BAD_REQUEST, "Invalid data value !");
            return;
        }
        $uuid = $param;
        $pseudo = $data['pseudo'];
        $displayName = $data['displayName'];
        $coins = $data['coins'];
        $level = $data['level'];
        $exp = $data['exp'];
        $lastLogin = $data['lastLogin'];
        $ip = $data['ip'];
        $lang = $data['lang'];
        // Check values
        if (strlen($uuid) != 36) {
            Response::error(Response::ERROR_BAD_REQUEST, "UUID length must be 36 !");
            return;
        }
        if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Bad UUID format !");
            return;
        }
        if (strlen($pseudo) < 1 || strlen($pseudo) > 20) {
            Response::error(Response::ERROR_BAD_REQUEST, "Pseudo length must be between 1 and 20 !");
            return;
        }
        if (strlen($displayName) < 1 || strlen($displayName) > 20) {
            Response::error(Response::ERROR_BAD_REQUEST, "DisplayName length must be between 1 and 20 !");
            return;
        }
        if ($coins < 0) {
            Response::error(Response::ERROR_BAD_REQUEST, "Coins must be positive !");
            return;
        }
        if ($level < 0) {
            // TODO Autoriser la suppression des niveaux (Genre on peut "acheter" des améliorations avec des niveaux, ...)
            Response::error(Response::ERROR_BAD_REQUEST, "Level must be positive !");
            return;
        }
        if ($exp < 0) {
            // TODO Idem que "level"
            Response::error(Response::ERROR_BAD_REQUEST, "Level must be positive !");
            return;
        }
        // lastLogin
        try {
            $lastLogin = new \DateTime($lastLogin);
        } catch (\Exception $ex) {
            Response::error(Response::ERROR_BAD_REQUEST, "Bad time format !");
            return;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Response::error(Response::ERROR_BAD_REQUEST, "Bad ip format !");
            return;
        }
        // TODO Vérifier si la langue est correcte
        if ($lang < 0) {
            Response::error(Response::ERROR_BAD_REQUEST, "Coins must be positive !");
            return;
        }
        // On récupère l'ancien joueur
        $p = $this->playerDataController->getUserByUUID($uuid);
        if (!$p) {
            Response::error(Response::ERROR_NOTFOUND, "Player not found !");
            return;
        }
        // On teste si lastLogin est bien égal ou plus grand que le lastLogin sauvegardé
        if ($lastLogin <= $p->getLastLogin()) {
            Response::error(Response::ERROR_BAD_REQUEST, "LastLogin must be greater than saved LastLogin !");
            return;
        }
        // Tout est bon, on update les valeurs
        $p->setPseudo($pseudo);
        $p->setDisplayName($displayName);
        $p->setCoins($coins);
        $p->setLevel($level);
        $p->setExp($exp);
        $p->setLastLogin($lastLogin);
        $p->setIp($ip);
        $p->setLevel($level);
        $p2 = $this->playerDataController->updateUser($p);
        if (!empty($GLOBALS['errorCode'])) {
            // Error
            Response::error(Response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($p2 == null) {
            // Unknown error
            Response::error(Response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        Response::ok([
            "uuid" => $p2->getUuid(),
            "pseudo" => $p2->getPseudo(),
            "displayName" => $p2->getDisplayName(),
            "coins" => $p2->getCoins(),
            "level" => $p2->getLevel(),
            "exp" => $p2->getExp(),
            "firstLogin" => Core::formatDate($p2->getFirstLogin()),
            "lastLogin" => Core::formatDate($p2->getLastLogin()),
            "ip" => $p2->getIp(),
            "lang" => $p2->getLang()
        ], Response::SUCCESS_OK);
        return;
    }
}
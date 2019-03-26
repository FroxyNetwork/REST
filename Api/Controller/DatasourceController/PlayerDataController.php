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

namespace Api\Controller\DatasourceController;

use Api\Model\PlayerModel;
use Web\Core\Core;

class PlayerDataController {
    const ERROR_NOT_FOUND = 1;
    const ERROR_UNKNOWN = 2;
    const name = "player";
    /**
     * @var \PDO $db
     */
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @param $uuid string User uuid
     * @return PlayerModel|bool
     */
    function getUserByUUID($uuid) {
        try {
            $sql = "SELECT * FROM ".self::name." WHERE uuid=:uuid";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":uuid", $uuid, \PDO::PARAM_STR);
            $prep->execute();
            if ($prep->rowCount() == 0) {
                $GLOBALS['error'] = "Player Not Found !";
                $GLOBALS['errorCode'] = self::ERROR_NOT_FOUND;
                return false;
            }
            $result = $prep->fetch();
            return new PlayerModel($result['uuid'], $result['pseudo'], $result['display_name'], $result['coins'], $result['level'], $result['exp'], new \DateTime($result['first_login']), new \DateTime($result['last_login']), $result['ip'], $result['lang']);
        } catch(\Exception $ex) {
            $GLOBALS['error'] = $ex->getMessage();
            $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
            return false;
        } finally {
            if (!is_null($prep))
                $prep->closeCursor();
            $prep = null;
        }
    }

    /**
     * @param $pseudo string Player name
     * @return PlayerModel|bool
     */
    function getUserByPseudo($pseudo) {
        try {
            $sql = "SELECT * FROM ".self::name." WHERE pseudo=:pseudo";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":pseudo", $pseudo, \PDO::PARAM_STR);
            $prep->execute();
            if ($prep->rowCount() == 0) {
                $GLOBALS['error'] = "Player Not Found !";
                $GLOBALS['errorCode'] = self::ERROR_NOT_FOUND;
                return false;
            }
            $result = $prep->fetch();
            return new PlayerModel($result['uuid'], $result['pseudo'], $result['display_name'], $result['coins'], $result['level'], $result['exp'], new \DateTime($result['first_login']), new \DateTime($result['last_login']), $result['ip'], $result['lang']);
        } catch(\Exception $ex) {
            $GLOBALS['error'] = $ex->getMessage();
            $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
            return false;
        } finally {
            if (!is_null($prep))
                $prep->closeCursor();
            $prep = null;
        }
    }

    /**
     * @param $uuid string L'UUID du joueur
     * @param $pseudo string Le pseudo du joueur
     * @param $ip string L'ip du joueur
     *
     * @return bool|PlayerModel false si erreur, ou le joueur
     */
    function createUser($uuid, $pseudo, $ip) {
        try {
            $now = new \DateTime();
            $p = new PlayerModel($uuid, $pseudo, $pseudo, 0, 1, 0, $now, $now, $ip, "fr_FR");
            $sql = "INSERT INTO ".self::name." (uuid, pseudo, display_name, coins, level, exp, first_login, last_login, ip, lang) VALUES (:uuid, :pseudo, :displayname, :coins, :level, :exp, :firstlogin, :lastlogin, :ip, :lang)";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":uuid", $p->getUuid(), \PDO::PARAM_STR);
            $prep->bindValue(":pseudo", $p->getPseudo(), \PDO::PARAM_STR);
            $prep->bindValue(":displayname", $p->getDisplayName(), \PDO::PARAM_STR);
            $prep->bindValue(":coins", $p->getCoins(), \PDO::PARAM_INT);
            $prep->bindValue(":level", $p->getLevel(), \PDO::PARAM_INT);
            $prep->bindValue(":exp", $p->getExp(), \PDO::PARAM_INT);
            $prep->bindValue(":firstlogin", Core::formatDate($p->getFirstLogin()), \PDO::PARAM_STR);
            $prep->bindValue(":lastlogin", Core::formatDate($p->getLastLogin()), \PDO::PARAM_STR);
            $prep->bindValue(":ip", $p->getIp(), \PDO::PARAM_STR);
            $prep->bindValue(":lang", $p->getLang(), \PDO::PARAM_STR);
            $prep->execute();
            if ($prep->rowCount() != 1) {
                $GLOBALS['error'] = "An error has occured while creating a player !";
                $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
                return false;
            }
            return $p;
        } catch (\Exception $ex) {
            $GLOBALS['error'] = $ex->getMessage();
            $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
            return false;
        } finally {
            if (!is_null($prep))
                $prep->closeCursor();
            $prep = null;
        }
    }

    /**
     * @param $p PlayerModel Le joueur
     *
     * @return bool|PlayerModel false si erreur, ou le joueur
     */
    function updateUser($p) {
        try {
            $sql = "UPDATE ".self::name." SET pseudo=:pseudo, display_name=:displayname, coins=:coins, level=:level, exp=:exp, last_login=:lastlogin, ip=:ip, lang=:lang WHERE uuid=:uuid";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":pseudo", $p->getPseudo(), \PDO::PARAM_STR);
            $prep->bindValue(":displayname", $p->getDisplayName(), \PDO::PARAM_STR);
            $prep->bindValue(":coins", $p->getCoins(), \PDO::PARAM_INT);
            $prep->bindValue(":level", $p->getLevel(), \PDO::PARAM_INT);
            $prep->bindValue(":exp", $p->getExp(), \PDO::PARAM_INT);
            $prep->bindValue(":lastlogin", Core::formatDate($p->getLastLogin()), \PDO::PARAM_STR);
            $prep->bindValue(":ip", $p->getIp(), \PDO::PARAM_STR);
            $prep->bindValue(":lang", $p->getLang(), \PDO::PARAM_STR);
            $prep->bindValue(":uuid", $p->getUuid(), \PDO::PARAM_STR);
            $prep->execute();
            if ($prep->rowCount() != 1) {
                $GLOBALS['error'] = "An error has occured while updating a player !";
                $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
                return false;
            }
            return $p;
        } catch (\Exception $ex) {
            $GLOBALS['error'] = $ex->getMessage();
            $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
            return false;
        } finally {
            if (!is_null($prep))
                $prep->closeCursor();
            $prep = null;
        }
    }
}
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

namespace Api\Controller\DatasourceController;

use Api\Model\PlayerModel;

class PlayerDataController {
    const ERROR_NOT_FOUND = 1;
    const ERROR_UNKNOWN = 2;
    const name = "player";
    /**
     * @var \PDO $db
     */
    private $db;

    public function __construct(\PDO $db) {
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
            var_dump($ex);
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
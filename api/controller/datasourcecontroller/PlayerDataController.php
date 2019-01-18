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

class PlayerDataController {
    const ERROR_NOT_FOUND = 1;
    const name = "player";
    /**
     * @var \PDO $db
     */
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    function getUserById($id) {
        try {
            $sql = "SELECT * FROM ".self::name." WHERE u_id=:id";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":id", $id, \PDO::PARAM_INT);
            $prep->execute();
            if ($prep->rowCount() == 0) {
                $GLOBALS['error'] = "Player Not Found !";
                $GLOBALS['errorCode'] = self::ERROR_NOT_FOUND;
                return false;
            }
            $result = $prep->fetch();
            return new \PlayerModel($result['u_uuid'], $result['u_pseudo'], $result['u_displayname'], $result['u_coins'], $result['u_level'], $result['u_exp'], $result['u_first_login'], $result['u_last_login'], $result['u_ip'], $result['u_lang']);
        } catch(\PDOException $e) {
            $GLOBALS['error'] = $e->getMessage();
            return false;
        } finally {
            if (!is_null($prep))
                $prep->closeCursor();
            $prep = null;
        }
    }
}
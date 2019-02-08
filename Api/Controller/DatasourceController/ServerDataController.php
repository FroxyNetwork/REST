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

use Api\Model\ServerModel;
use Api\Model\ServerStatus;
use Web\Core\Core;

class ServerDataController {
    const ERROR_NOT_FOUND = 1;
    const ERROR_UNKNOWN = 2;
    const name = "server";
    /**
     * @var \PDO $db
     */
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @param $id int L'id du serveur
     * @return ServerModel|bool
     */
    function getServer($id) {
        try {
            $sql = "SELECT * FROM ".self::name." WHERE id=:id";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":id", $id, \PDO::PARAM_INT);
            $prep->execute();
            if ($prep->rowCount() == 0) {
                $GLOBALS['error'] = "Server Not Found !";
                $GLOBALS['errorCode'] = self::ERROR_NOT_FOUND;
                return false;
            }
            $result = $prep->fetch();
            return new ServerModel($result['id'], $result['name'], $result['port'], $result['status'], new \DateTime($result['creation_time']), $result['scope']);
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
     * Retourne tout les serveurs ouverts
     * Un serveur est ouvert si son status est:
     * - STARTING
     * - WAITING
     * - STARTED
     * - ENDING
     * @return ServerModel[]
     */
    function getOpenedServers() {
        try {
            $sql = "SELECT * FROM ".self::name." WHERE status=:st1 OR status=:st2 OR status=:st3 OR status=:st4";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":st1", ServerStatus::STARTING, \PDO::PARAM_STR);
            $prep->bindValue(":st2", ServerStatus::WAITING, \PDO::PARAM_STR);
            $prep->bindValue(":st3", ServerStatus::STARTED, \PDO::PARAM_STR);
            $prep->bindValue(":st4", ServerStatus::ENDING, \PDO::PARAM_STR);
            $prep->execute();
            $arr = $prep->fetchAll();
            $result = [];
            foreach ($arr as $value)
                $result[] = new ServerModel($value['id'], $value['name'], $value['port'], $value['status'], new \DateTime($value['creation_time']), $value['scope']);
            return $result;
        } catch(\Exception $ex) {
            $GLOBALS['error'] = $ex->getMessage();
            $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
            return [];
        } finally {
            if (!is_null($prep))
                $prep->closeCursor();
            $prep = null;
        }
    }

    /**
     * @param $name string Le nom du serveur
     * @param $port int Le port du serveur
     *
     * @return bool|ServerModel false si erreur, ou le serveur
     */
    function createServer($name, $port) {
        try {
            $now = new \DateTime();
            // TODO Scope
            $s = new ServerModel(0, $name, $port, ServerStatus::STARTING, $now, "");
            $sql = "INSERT INTO ".self::name." (name, port, status, creation_time, scope) VALUES (:name, :port, :status, :creation_time, :scope)";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":name", $s->getName(), \PDO::PARAM_STR);
            $prep->bindValue(":port", $s->getPort(), \PDO::PARAM_INT);
            $prep->bindValue(":status", $s->getStatus(), \PDO::PARAM_STR);
            $prep->bindValue(":creation_time", Core::formatDate($s->getCreationTime()), \PDO::PARAM_STR);
            $prep->bindValue(":scope", $s->getScope(), \PDO::PARAM_STR);
            $prep->execute();
            if ($prep->rowCount() != 1) {
                $GLOBALS['error'] = "An error has occured while creating a server !";
                $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
                return false;
            }
            $s->setId($this->db->lastInsertId());
            return $s;
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
     * @param $s ServerModel Le server
     *
     * @return bool|ServerModel false si erreur, ou le serveur
     */
    function updateServer($s) {
        try {
            $sql = "UPDATE ".self::name." SET status=:status WHERE id=:id";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":status", $s->getStatus(), \PDO::PARAM_STR);
            $prep->bindValue(":id", $s->getId(), \PDO::PARAM_INT);
            $prep->execute();
            if ($prep->rowCount() != 1) {
                $GLOBALS['error'] = "An error has occured while updating a server !";
                $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
                return false;
            }
            return $s;
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
     * @param $id int L'id du server
     *
     * @return bool True si fermé
     */
    function closeServer($id) {
        try {
            $sql = "UPDATE ".self::name." SET status=:status WHERE id=:id";
            $prep = $this->db->prepare($sql);
            $prep->bindValue(":status", ServerStatus::ENDED, \PDO::PARAM_STR);
            $prep->bindValue(":id", $id, \PDO::PARAM_INT);
            $prep->execute();
            if ($prep->rowCount() != 1) {
                $GLOBALS['error'] = "An error has occured while closing a server !";
                $GLOBALS['errorCode'] = self::ERROR_UNKNOWN;
                return false;
            }
            return true;
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
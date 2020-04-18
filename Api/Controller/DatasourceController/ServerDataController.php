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

use Api\Model\ServerStatus;
use Grpc\Server;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Web\Controller\DBController;

class ServerDataController {
    /**
     * @var Collection $db
     */
    private $db;

    public function __construct(DBController $db) {
        $this->db = $db->get("server");
    }

    /**
     * @param $id int L'id du serveur
     * @return array|bool
     */
    function getServer($id) {
        try {
            $c = $this->db->findOne(["_id" => new ObjectId($id)]);
            if (empty($c) || !$c)
                return false;
            $server = [
                'id' => (string) $c['_id'],
                'name' => $c['name'],
                'type' => $c['type'],
                'port' => $c['port'],
                'status' => $c['status'],
                'creation_time' => $c['creation_time']->toDateTime(),
                'end_time' => (!isset($c['end_time']) || is_null($c['end_time'])) ? null : $c['end_time']->toDateTime()
            ];
            if (isset($c['docker'])) {
                $server['docker'] = [];
                $server['docker']['server'] = $c['docker']['server'];
                $server['docker']['id'] = $c['docker']['id'];
            }
            return $server;
        } catch(\Exception $ex) {
            return false;
        }
    }

    /**
     * Vérifie si l'id du docker est déjà sauvegardé
     *
     * @param $id string L'id du serveur
     * @return true si l'id du docker est déjà sauvegardé
     */
    function checkServerDocker($id) {
        try {
            $c = $this->db->findOne(['_id' => new ObjectId($id)]);
            if (empty($c) || !$c)
                return false;
            return (array_key_exists('docker', $c) && array_key_exists('server', $c['docker']) && array_key_exists('id', $c['docker']) && !empty($c['docker']['server']) && !empty($c['docker']['id']));
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $id string L'id du serveur
     * @param $server int L'id du serveur où tourne le docker
     * @param $docker string L'id du docker
     * @return bool
     */
    function updateServerDocker($id, $server, $docker) {
        try {
            $c = $this->db->updateOne(['_id' => new ObjectId($id)], ['$set' => ['docker' => [
                'server' => $server,
                'id' => $docker
            ]]]);
            if ($c->getModifiedCount() != 1)
                return false;
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Retourne tout les serveurs ouverts
     * Un serveur est ouvert si son statut est:
     * - STARTING
     * - WAITING
     * - STARTED
     * - ENDING
     * @param $mode integer 1 = only servers, 2 = only bungees, 3 = servers and bungees
     * @return array[]
     */
    function getOpenedServers($mode) {
        try {
            switch ($mode) {
                case 1:
                    $req = ['$and' => [['status' => ['$in' => [ServerStatus::STARTING, ServerStatus::WAITING, ServerStatus::STARTED, ServerStatus::ENDING]]], ['type' => ['$ne' => 'BUNGEE']]]];
                    break;
                case 2:
                    $req = ['$and' => [['status' => ['$in' => [ServerStatus::STARTING, ServerStatus::WAITING, ServerStatus::STARTED, ServerStatus::ENDING]]], ['type' => 'BUNGEE']]];
                    break;
                case 3:
                default:
                    $req = ['status' => ['$in' => [ServerStatus::STARTING, ServerStatus::WAITING, ServerStatus::STARTED, ServerStatus::ENDING]]];
            }
            $c = $this->db->find($req);
            if (empty($c) || $c == null)
                return [];
            $s = $c->toArray();
            if ($s == null || !$s || !is_array($s))
                return [];
            $servers = [];
            foreach ($s as $v) {
                $arr = [
                    'id' => (string) $v['_id'],
                    'name' => $v['name'],
                    'type' => $v['type'],
                    'port' => $v['port'],
                    'status' => $v['status'],
                    'creation_time' => $v['creation_time']->toDateTime(),
                    'end_time' => (!isset($v['end_time']) || is_null($v['end_time'])) ? null : $v['end_time']->toDateTime()
                ];
                if (isset($v['docker'])) {
                    $arr['docker'] = [];
                    $arr['docker']['server'] = $v['docker']['server'];
                    $arr['docker']['id'] = $v['docker']['id'];
                }
                $servers[] = $arr;
            }
            return $servers;
        } catch(\Exception $ex) {
            return [];
        }
    }

    /**
     * @param $name string Le nom du serveur
     * @param $type string Le type de serveur
     * @param $port int Le port du serveur
     *
     * @return array|bool false si erreur, ou le serveur
     */
    function createServer($name, $type, $port) {
        try {
            $now = new \DateTime();
            $server = [
                'name' => $name,
                'type' => $type,
                'port' => $port,
                'status' => ServerStatus::STARTING,
                'creation_time' => $now
            ];
            $c = $this->db->insertOne(['name' => $server['name'], 'type' => $server['type'], 'port' => $server['port'], 'status' => $server['status'], 'creation_time' => new UTCDateTime($server['creation_time']->getTimestamp() * 1000)]);
            if ($c->getInsertedCount() != 1)
                return false;
            $server['id'] = (string) $c->getInsertedId();
            return $server;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $s array Le server
     *
     * @return array|bool false si erreur, ou le serveur
     */
    function updateServer($s) {
        try {
            $c = $this->db->updateOne(['_id' => new ObjectId($s['id'])], ['$set' => ['status' => $s['status']]]);
            if ($c->getModifiedCount() != 1)
                return false;
            return $s;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $id int L'id du server
     *
     * @return bool True si fermé
     */
    function closeServer($id) {
        try {
            $now = new \DateTime();
            $c = $this->db->updateOne(['_id' => new ObjectId($id)], ['$set' => ['status' => ServerStatus::ENDED, 'end_time' => new UTCDateTime($now->getTimestamp() * 1000)]]);
            return $c->getModifiedCount() == 1;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $id int L'id du server
     *
     * @return bool True si supprimé
     */
    function deleteServer($id) {
        try {
            $c = $this->db->deleteOne(['_id' => new ObjectId($id)]);
            return $c->getDeletedCount() == 1;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
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


namespace Api\Controller\DatasourceController;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Web\Controller\DBController;

class PlayerDataController {
    /**
     * @var Collection $db
     */
    private $db;

    public function __construct(DBController $db) {
        $this->db = $db->get("player");
    }

    /**
     * @param $uuid string User uuid
     * @return array|bool
     */
    function getUserByUUID($uuid) {
        try {
            $c = $this->db->findOne(["_id" => $uuid]);
            if (empty($c) || !$c)
                return false;
            $user = [
                'uuid' => $c['_id'],
                'nickname' => $c['nickname'],
                'display_name' => $c['display_name'],
                'coins' => $c['coins'],
                'level' => $c['level'],
                'exp' => $c['exp'],
                'first_login' => $c['first_login']->toDateTime(),
                'last_login' => $c['last_login']->toDateTime(),
                'ip' => $c['ip'],
                'lang' => $c['lang'],
				'saved' => $c['saved']
            ];
            if (isset($c['server']))
                $user['server'] = $c['server'];
            return $user;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $nickname string Player name
     * @return array|bool
     */
    function getUserByPseudo($nickname) {
        try {
            $c = $this->db->findOne(["nickname" => $nickname]);
            if (empty($c) || !$c)
                return false;
            $user = [
                'uuid' => $c['_id'],
                'nickname' => $c['nickname'],
                'display_name' => $c['display_name'],
                'coins' => $c['coins'],
                'level' => $c['level'],
                'exp' => $c['exp'],
                'first_login' => $c['first_login']->toDateTime(),
                'last_login' => $c['last_login']->toDateTime(),
                'ip' => $c['ip'],
                'lang' => $c['lang'],
                'server' => $c['server'],
				'saved' => $c['saved']
            ];
            if (isset($c['server']))
                $user['server'] = $c['server'];
            return $user;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $uuid string L'UUID du joueur
     * @param $nickname string Le pseudo du joueur
     * @param $ip string L'ip du joueur
     *
     * @return array|bool false si erreur, ou le joueur
     */
    function createUser($uuid, $nickname, $ip) {
        try {
            $now = new \DateTime();
            $user = [
                'uuid' => strtolower($uuid),
                'nickname' => strtolower($nickname),
                'display_name' => strtolower($nickname),
                'coins' => 0,
                'level' => 0,
                'exp' => 0,
                'first_login' => $now,
                'last_login' => $now,
                'ip' => $ip,
                'lang' => 'fr_FR',
				'saved' => true
            ];
            $c = $this->db->insertOne(['_id' => $user['uuid'], 'nickname' => $user['nickname'], 'display_name' => $user['display_name'], 'coins' => $user['coins'], 'level' => $user['level'], 'exp' => $user['exp'], 'first_login' => new UTCDateTime($user['first_login']->getTimestamp() * 1000), 'last_login' => new UTCDateTime($user['last_login']->getTimestamp() * 1000), 'ip' => $user['ip'], 'lang' => $user['lang'], 'saved' => $user['saved']]);
            if ($c->getInsertedCount() != 1)
                return false;
            return $user;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $p array Le joueur
     *
     * @return array|bool false si erreur, ou le joueur
     */
    function updateUser($p, $saved = false) {
        try {
			$set = ['nickname' => $p['nickname'], 'display_name' => $p['display_name'], 'coins' => $p['coins'], 'level' => $p['level'], 'exp' => $p['exp'], 'last_login' => new UTCDateTime($p['last_login']->getTimestamp() * 1000), 'ip' => $p['ip'], 'lang' => $p['lang'], 'server' => $p['server'], 'saved' => $p['saved']];
			if ($saved)
				$set['saved'] = $p['saved'];
            $c = $this->db->updateOne(["_id" => $p['uuid']], ['$set' => $set]);
            if ($c->getModifiedCount() != 1)
                return false;
            return $p;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
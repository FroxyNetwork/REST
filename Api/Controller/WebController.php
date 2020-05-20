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

namespace Api\Controller;

use Api\Controller\DatasourceController\OAuth2DataController;
use MongoDB\Database;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\Server;
use Web\Controller\AppController;

class WebController extends AppController {
    /**
     * @var Database $db
     */
    private $db;

    /**
     * Méthode appelée quand l'app charge
     * Utilisé pour charger les utilisateurs, etc
     * Exemple de structure pour $list:
     * [
     *      "user" : $user
     * ]
     * Donc, pour tout les contrôleurs, il y aura une variable $user avec comme valeur user
     *
     * @param array $list Une liste de variables à ajouter aux controleurs
     * @param Database $db La connexion bdd
     */
    function onLoad(array &$list, Database $db) {
        $this->db = $db;
        // OAuth2
        $this->loadOAuth2($list);
        $this->loadServerConfig($list);
    }

    function loadOAuth2(array &$list) {
        // TODO Sauvegarder sur une autre bdd (Ou via un autre utilisateur / une autre connexion)
        //$storage = new Pdo($this->pdo);
        $storage = new OAuth2DataController($this->db);
        $server = new Server($storage);
        $server->addGrantType(new ClientCredentials($storage));
        $server->addGrantType(new RefreshToken($storage));
        $list['oauth'] = $server;
        $list['oauth_storage'] = $storage;
    }

    function loadServerConfig(array &$list) {
        $serverConfig = new ServerConfig();
        $list['serverConfig'] = $serverConfig;
    }

    /**
     * Méthode appelée quand l'app décharge
     */
    function onUnload() {

    }

    public function implementedMethods() {
        return [];
    }
}
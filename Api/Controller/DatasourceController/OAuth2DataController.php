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

use OAuth2\Storage\Mongo;

/**
 * Extension de la classe @see Mongo
 *
 * Class OAuth2DataController
 * @package Api\Controller\DatasourceController
 */
class OAuth2DataController extends NewMongo {
    /*
     * $config :
     * ---------------------------------------------------------------------
     *          Clé         =>             Défaut           =>  FroxyNetwork
     * ---------------------------------------------------------------------
     * client_table         => oauth_clients
     * access_token_table   => oauth_access_tokens
     * code_table           => oauth_authorization_codes
     * refresh_token_table  => oauth_refresh_tokens
     * user_table           => oauth_users                  => server
     * scope_table          => oauth_scopes
     * jwt_table            => oauth_jwt
     */

    public function __construct($connection, array $config = array()) {
        $config['user_table'] = 'server';
        parent::__construct($connection, $config);
    }

    /**
     * NOT USED HERE
     */
    protected function checkPassword($user, $password) {
        return false;
    }

    public function getUser($id) {
        return $this->getServer($id);
    }

    public function getServer($id) {
        $result = $this->collection('user_table')->findOne(['id' => $id]);

        return is_null($result) ? false : $result;
    }

    /**
     * NOT USED HERE
     */
    public function setUser($username, $password, $firstName = null, $lastName = null) {
        return false;
    }

    /**
     * @param $client_id string The client_id
     * @param $client_secret string The client_secret
     * @param $scope string The scope of the client
     * @param $user_id int The id of the server
     * @return bool True if succesfully created
     */
    public function createClient($client_id, $client_secret, $scope, $user_id) {
        return $this->setClientDetails($client_id, $client_secret, null, null, $scope, $user_id);
    }
}
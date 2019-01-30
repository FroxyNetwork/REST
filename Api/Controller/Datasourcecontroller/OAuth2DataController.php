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

use OAuth2\Storage\Pdo;

/**
 * Extension de la classe @see Pdo
 *
 * Class OAuth2DataController
 * @package Api\Controller\DatasourceController
 */
class OAuth2DataController extends Pdo {
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
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where id=:id', $this->config['user_table']));
        $stmt->execute(array('id' => $id));

        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        // the default behavior is to use "username" as the user_id
        return array_merge(array(
            'user_id' => $id
        ), $userInfo);
    }

    /**
     * NOT USED HERE
     */
    public function setUser($username, $password, $firstName = null, $lastName = null) {
        return false;
    }

    public function getBuildSql($dbName = 'froxynetwork') {
        $sql = "
        CREATE TABLE {$this->config['client_table']} (
          client_id            VARCHAR(80)   NOT NULL,
          client_secret        VARCHAR(80),
          redirect_uri         VARCHAR(2000),
          grant_types          VARCHAR(80),
          scope                VARCHAR(4000),
          user_id              VARCHAR(80),
          PRIMARY KEY (client_id)
        );
        CREATE TABLE {$this->config['access_token_table']} (
          access_token         VARCHAR(40)    NOT NULL,
          client_id            VARCHAR(80)    NOT NULL,
          user_id              VARCHAR(80),
          expires              TIMESTAMP      NOT NULL,
          scope                VARCHAR(4000),
          PRIMARY KEY (access_token)
        );
        CREATE TABLE {$this->config['code_table']} (
          authorization_code   VARCHAR(40)    NOT NULL,
          client_id            VARCHAR(80)    NOT NULL,
          user_id              VARCHAR(80),
          redirect_uri         VARCHAR(2000),
          expires              TIMESTAMP      NOT NULL,
          scope                VARCHAR(4000),
          id_token             VARCHAR(1000),
          PRIMARY KEY (authorization_code)
        );
        CREATE TABLE {$this->config['refresh_token_table']} (
          refresh_token        VARCHAR(40)    NOT NULL,
          client_id            VARCHAR(80)    NOT NULL,
          user_id              VARCHAR(80),
          expires              TIMESTAMP      NOT NULL,
          scope                VARCHAR(4000),
          PRIMARY KEY (refresh_token)
        );
        CREATE TABLE {$this->config['user_table']} (
          id                   INT(11)        NOT NULL AUTO_INCREMENT,
          name                 VARCHAR(16)    NOT NULL,
          port                 INT(11),
          status               VARCHAR(16)    NOT NULL,
          creation_time        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
          scope                VARCHAR(4000)
        );
        CREATE TABLE {$this->config['scope_table']} (
          scope                VARCHAR(80)  NOT NULL,
          is_default           BOOLEAN,
          PRIMARY KEY (scope)
        );
        CREATE TABLE {$this->config['jwt_table']} (
          client_id            VARCHAR(80)   NOT NULL,
          subject              VARCHAR(80),
          public_key           VARCHAR(2000) NOT NULL
        );
        CREATE TABLE {$this->config['jti_table']} (
          issuer               VARCHAR(80)   NOT NULL,
          subject              VARCHAR(80),
          audiance             VARCHAR(80),
          expires              TIMESTAMP     NOT NULL,
          jti                  VARCHAR(2000) NOT NULL
        );
        CREATE TABLE {$this->config['public_key_table']} (
          client_id            VARCHAR(80),
          public_key           VARCHAR(2000),
          private_key          VARCHAR(2000),
          encryption_algorithm VARCHAR(100) DEFAULT 'RS256'
        )
        ";
        return $sql;
    }
}
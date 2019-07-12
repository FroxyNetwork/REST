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
 */

namespace Api\Controller\DatasourceController;

use MongoDB\Client;
use MongoDB\Database;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\JwtBearerInterface;
use OAuth2\Storage\PublicKeyInterface;
use OAuth2\Storage\RefreshTokenInterface;
use OAuth2\Storage\ScopeInterface;
use OAuth2\Storage\UserCredentialsInterface;

class NewMongo implements
    AuthorizationCodeInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    UserCredentialsInterface,
    RefreshTokenInterface,
    JwtBearerInterface,
    ScopeInterface,
    PublicKeyInterface
{
    /**
     * @var $db Database
     */
    protected $db;
    protected $config;

    public function __construct($connection, $config = array())
    {
        if ($connection instanceof Database) {
            $this->db = $connection;
        } else {
            if (!is_array($connection)) {
                throw new \InvalidArgumentException('First argument to OAuth2\Storage\Mongo must be an instance of Database or a configuration array');
            }
            $server = sprintf('mongodb://%s:%d', $connection['host'], $connection['port']);
            $m = new Client($server);
            $this->db = $m->{$connection['database']};
        }

        $this->config = array_merge(array(
            'client_table' => 'oauth_clients',
            'access_token_table' => 'oauth_access_tokens',
            'refresh_token_table' => 'oauth_refresh_tokens',
            'code_table' => 'oauth_authorization_codes',
            'user_table' => 'oauth_users',
            'key_table' => 'oauth_keys',
            'jwt_table' => 'oauth_jwt',
            'jti_table'  => 'oauth_jti',
            'scope_table'  => 'oauth_scopes',
        ), $config);
    }

    // Helper function to access a MongoDB collection by `type`:
    protected function collection($name)
    {
        return $this->db->selectCollection($this->config[$name]);
    }

    /* ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        if ($result = $this->collection('client_table')->findOne(array('client_id' => $client_id))) {
            return $result['client_secret'] == $client_secret;
        }

        return false;
    }

    public function isPublicClient($client_id)
    {
        if (!$result = $this->collection('client_table')->findOne(array('client_id' => $client_id))) {
            return false;
        }

        return empty($result['client_secret']);
    }

    /* ClientInterface */
    public function getClientDetails($client_id)
    {
        $result = $this->collection('client_table')->findOne(array('client_id' => $client_id));

        return is_null($result) ? false : $result;
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        if ($this->getClientDetails($client_id)) {
            $this->collection('client_table')->updateOne(
                array('client_id' => $client_id),
                array('$set' => array(
                    'client_secret' => $client_secret,
                    'redirect_uri'  => $redirect_uri,
                    'grant_types'   => $grant_types,
                    'scope'         => $scope,
                    'user_id'       => $user_id,
                ))
            );
        } else {
            $client = array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_types'   => $grant_types,
                'scope'         => $scope,
                'user_id'       => $user_id,
            );
            $this->collection('client_table')->insertOne($client);
        }

        return true;
    }

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, $grant_types);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /* AccessTokenInterface */
    public function getAccessToken($access_token)
    {
        $token = $this->collection('access_token_table')->findOne(array('access_token' => $access_token));

        return is_null($token) ? false : $token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $this->collection('access_token_table')->updateOne(
                array('access_token' => $access_token),
                array('$set' => array(
                    'client_id' => $client_id,
                    'expires' => $expires,
                    'user_id' => $user_id,
                    'scope' => $scope
                ))
            );
        } else {
            $token = array(
                'access_token' => $access_token,
                'client_id' => $client_id,
                'expires' => $expires,
                'user_id' => $user_id,
                'scope' => $scope
            );
            $this->collection('access_token_table')->insertOne($token);
        }

        return true;
    }

    public function unsetAccessToken($access_token)
    {
        $result = $this->collection('access_token_table')->deleteOne(array(
            'access_token' => $access_token
        ), array('w' => 1));

        return $result['n'] > 0;
    }


    /* AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        $code = $this->collection('code_table')->findOne(array('authorization_code' => $code));

        return is_null($code) ? false : $code;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
            $this->collection('code_table')->updateOne(
                array('authorization_code' => $code),
                array('$set' => array(
                    'client_id' => $client_id,
                    'user_id' => $user_id,
                    'redirect_uri' => $redirect_uri,
                    'expires' => $expires,
                    'scope' => $scope,
                    'id_token' => $id_token,
                ))
            );
        } else {
            $token = array(
                'authorization_code' => $code,
                'client_id' => $client_id,
                'user_id' => $user_id,
                'redirect_uri' => $redirect_uri,
                'expires' => $expires,
                'scope' => $scope,
                'id_token' => $id_token,
            );
            $this->collection('code_table')->insertOne($token);
        }

        return true;
    }

    public function expireAuthorizationCode($code)
    {
        $this->collection('code_table')->deleteOne(array('authorization_code' => $code));

        return true;
    }

    /* UserCredentialsInterface */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }

        return false;
    }

    public function getUserDetails($username)
    {
        if ($user = $this->getUser($username)) {
            $user['user_id'] = $user['username'];
        }

        return $user;
    }

    /* RefreshTokenInterface */
    public function getRefreshToken($refresh_token)
    {
        $token = $this->collection('refresh_token_table')->findOne(array('refresh_token' => $refresh_token));

        return is_null($token) ? false : $token;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        $token = array(
            'refresh_token' => $refresh_token,
            'client_id' => $client_id,
            'user_id' => $user_id,
            'expires' => $expires,
            'scope' => $scope
        );
        $this->collection('refresh_token_table')->insertOne($token);

        return true;
    }

    public function unsetRefreshToken($refresh_token)
    {
        $result = $this->collection('refresh_token_table')->deleteOne(array(
            'refresh_token' => $refresh_token
        ), array('w' => 1));

        return $result['n'] > 0;
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $password;
    }

    public function getUser($username)
    {
        $result = $this->collection('user_table')->findOne(array('username' => $username));

        return is_null($result) ? false : $result;
    }

    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
        if ($this->getUser($username)) {
            $this->collection('user_table')->updateOne(
                array('username' => $username),
                array('$set' => array(
                    'password' => $password,
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ))
            );
        } else {
            $user = array(
                'username' => $username,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName
            );
            $this->collection('user_table')->insertOne($user);
        }

        return true;
    }

    public function getClientKey($client_id, $subject)
    {
        $result = $this->collection('jwt_table')->findOne(array(
            'client_id' => $client_id,
            'subject' => $subject
        ));

        return is_null($result) ? false : $result['key'];
    }

    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }

        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }

        return null;
    }

    public function getJti($client_id, $subject, $audience, $expiration, $jti)
    {
        //TODO: Needs mongodb implementation.
        throw new \Exception('getJti() for the MongoDB driver is currently unimplemented.');
    }

    public function setJti($client_id, $subject, $audience, $expiration, $jti)
    {
        //TODO: Needs mongodb implementation.
        throw new \Exception('setJti() for the MongoDB driver is currently unimplemented.');
    }

    public function getPublicKey($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(array(
                'client_id' => $client_id
            ));
            if ($result) {
                return $result['public_key'];
            }
        }

        $result = $this->collection('key_table')->findOne(array(
            'client_id' => null
        ));
        return is_null($result) ? false : $result['public_key'];
    }

    public function getPrivateKey($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(array(
                'client_id' => $client_id
            ));
            if ($result) {
                return $result['private_key'];
            }
        }

        $result = $this->collection('key_table')->findOne(array(
            'client_id' => null
        ));
        return is_null($result) ? false : $result['private_key'];
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(array(
                'client_id' => $client_id
            ));
            if ($result) {
                return $result['encryption_algorithm'];
            }
        }

        $result = $this->collection('key_table')->findOne(array(
            'client_id' => null
        ));
        return is_null($result) ? 'RS256' : $result['encryption_algorithm'];
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function scopeExists($scope) {
        $scope = explode(' ', $scope);
        $count = $this->collection('scope_table')->countDocuments(["scope" => ['$in' => $scope]]);
        return $count == count($scope);
    }

    /**
     * @param mixed $client_id
     * @return null|string
     */
    public function getDefaultScope($client_id = null) {
        $result = $this->collection('scope_table')->find(["is_default" => true]);
        if ($result == null || !$result)
            return false;
        $arr = $result->toArray();
        if (!array($arr) || !$arr)
            return false;
        $defaultScope = array_map(function ($row) {
            return $row['scope'];
        }, $arr);

        return implode(' ', $defaultScope);
    }
}
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
 */

namespace Web\Core;

use Api\Controller\WebController;
use Web\Controller\AppController;
use Web\Controller\DBController;
use Web\Controller\RequestController;
use Web\Controller\ResponseController;

class Core {
    const MODELS = 0;
    const CONTROLLERS = 1;
    const DATASOURCES = 2;

    const _dir = [
        self::MODELS => ['model', 'Model'],
        self::CONTROLLERS => ['controller', 'Controller'],
        self::DATASOURCES => ['controller'.DS.'datasourceController', 'DataController']
    ];

    /**
     * @var RequestController Le controleur de requêtes
     */
    private $_requestController;

    /**
     * @var ResponseController Le controleur de réponse
     */
    private $_responseController;

    /**
     * @var DBController Le controleur de données
     */
    private $database;

    /**
     * @var array La configuration
     */
    private $config;

    /**
     * @var array Liste des variables à ajouter aux controleurs
     */
    private $list = [];

    private $init = false;

    public function init() {
        if ($this->init)
            return;
        $this->init = true;
        // Disable error reporting
        //error_reporting(0);
        // Fix HTTP_AUTHORIZATION
        $httpAuthorization = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION']))
            $httpAuthorization = $_SERVER["HTTP_AUTHORIZATION"];
        else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
            $httpAuthorization = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
        else if (isset($_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']))
            $httpAuthorization = $_SERVER["REDIRECT_REDIRECT_HTTP_AUTHORIZATION"];
        if ($httpAuthorization !== null) {
            if (stripos($httpAuthorization, "basic ") === 0) {
                // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                $exploded = explode(':', base64_decode(substr($httpAuthorization, 6)), 2);
                if (2 == count($exploded))
                    list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $exploded;
            } else if (empty($_SERVER['PHP_AUTH_DIGEST']) && (stripos($httpAuthorization, "digest ") === 0)) {
                $_SERVER['PHP_AUTH_DIGEST'] = $httpAuthorization;
            } else if (stripos($httpAuthorization, "bearer ") === 0) {
                // nginx is so bugged ...
                $_SERVER['AUTHORIZATION'] = $httpAuthorization;
                $_SERVER['HTTP_AUTHORIZATION'] = $httpAuthorization;
            }
        }
        // Initialisation des controleurs
        $this->_requestController = new RequestController($this);
        $this->_responseController = new ResponseController($this);
        // Load config file
        try {
            $this->config = parse_ini_file(API_DIR.DS."Config".DS."config.ini");
        } catch (\Exception $ex) {
            $this->config = false;
        } finally {
            if (!$this->config) {
                // config.ini file error
                $this->_responseController->error(ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_CONFIG);
                return;
            }
        }
        if (!isset($this->config['mongodb']) || !isset($this->config['mongodb_database'])) {
            // config.ini file error
            $this->_responseController->error(ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_CONFIG_MONGODB);
            return;
        }
        try {
            $this->database = new DBController($this->config['mongodb'], $this->config['mongodb_database']);
        } catch (\Exception $ex) {
            $this->_responseController->error(ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_DATABASE);
            return;
        }
        $this->list = array();
        // Including Routage
        Route::configure(isset($this->config['default_route']) ? $this->config['default_route'] : "/");
        include API_DIR.DS."Config".DS."Routage.php";
        $path = $this->_requestController->getPath();
        $route = Route::getRoute($path);
        if (!$route || !is_array($route) || sizeof($route) == 0) {
            $this->_responseController->error($this->_responseController::ERROR_NOTFOUND, Error::ROUTE_NOT_FOUND);
            return;
        }
        $controller = $route['controller'];
        $params = $route['param'];
        /**
         * @var $webController WebController
         */
        $webController = $this->getAppController("Web");
        $webController->onLoad($this->list, $this->database->getDatabase());
        try {
            $appController = $this->getAppController($controller);
            $this->_responseController->setAppController($appController);
            if ($appController == null) {
                // Erreur
                $this->_responseController->error($this->_responseController::ERROR_NOTFOUND, Error::GLOBAL_CONTROLLER_NOT_FOUND);
                return;
            }
            switch ($this->_requestController->getMethod()) {
                case "GET":
                    $appController->get($params);
                    break;
                case "POST":
                    $appController->post($params);
                    break;
                case "PUT":
                    $appController->put($params);
                    break;
                case "DELETE":
                    $appController->delete($params);
                    break;
                case "HEAD":
                    $appController->head($params);
                    break;
                case "OPTIONS":
                    $appController->options($params);
                    break;
                default:
                    $appController->other($params);
                    break;
            }

        } catch (\Exception $ex) {
            // Erreur
            $this->_responseController->error($this->_responseController::SERVER_INTERNAL, Error::GLOBAL_UNKNOWN_ERROR);
        }

        //$appController->$action($params);
        $webController->onUnload();
        $this->database->close();
    }

    /**
     * Récupère l'emplacement du fichier spécifié
     * Exemples:
     * $this->core->path(Core::MODELS, "User");
     *  ==> <...>/api/model/UserModel.php
     * $this->core->path(Core::CONTROLLERS, "Auth");
     *  ==> <...>/api/controller/AuthController.php
     * $this->core->path(Core::DATASOURCES, "User");
     *  ==> <...>/api/controller/DatasourceController/UserDataController.php
     * $this->core->path("TypeIncorrect", "User");
     *  ==> <...>/api/TypeIncorrect/User.php
     *
     * @param string $type Le type (MODELS, CONTROLLERS, DATASOURCES)
     * @param string $name Le nom du fichier
     * @return string The path of the file.
     */
    public function path($type, $name) {
        if (isset($this->_dir[$type]) && !empty(self::_dir[$type][0]))
            return API_DIR.DS.self::_dir[$type][0].DS.$this->fileName($type, $name).".php";
        return API_DIR.DS.$type.DS.$this->fileName($type, $name).".php";
    }

    /**
     * Récupère le nom du fichier spécifié
     *
     * @param string $type Le type (MODELS, CONTROLLERS, DATASOURCES)
     * @param string $name Le nom du fichier
     * @return string Le nom du fichier (sans .php)
     */
    public function fileName($type, $name) {
        if (!is_int($type) || empty($name))
            return ucfirst(strtolower($name));
        if (!empty(self::_dir[$type][0]))
            return ucfirst(strtolower($name)).self::_dir[$type][1];
        return ucfirst(strtolower($name));
    }

    /**
     * // TODO Sauvegarder les anciens controleurs créés (Eviter la duplication)
     * Retourne le controleur spécifique
     * @param string $name Le nom du controleur
     * @return AppController Le controleur spécifique
     */
    public function getAppController($name) {
        $file = $this->fileName(self::CONTROLLERS, $name);
        /**
         * @var AppController $impl
         */
        $class = "\\Api\\Controller\\".$file;
        $impl = new $class($this);
        // Ajout des autres controleurs
        $impl->request = $this->_requestController;
        $impl->response = $this->_responseController;
        foreach ($this->list as $key => $value)
            $impl->$key = $value;
        return $impl;
    }

    /**
     * // TODO Sauvegarder les anciens controleurs créés (Eviter la duplication)
     * @param string $name Le nom de la source de données
     * @return mixed La source de données spécifique
     */
    public function getDataController($name) {
        $file = $this->fileName(self::DATASOURCES, $name);
        $class = "\\Api\\Controller\\DatasourceController\\".$file;
        if ($this->database != null)
            $impl = new $class($this->database);
        else
            $impl = new $class(null);
        return $impl;
    }

    public function startsWith($haystack, $needle) {
        if ($haystack === null)
            return $needle === null;
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public function endsWith($haystack, $needle) {
        if ($haystack === null)
            return $needle === null;
        if ($needle === null)
            return false;
        $length = strlen($needle);

        return $length === 0 ||
            (substr($haystack, -$length) === $needle);
    }

    /**
     * @param $dateTime \DateTime
     * @return string Formated date
     */
    public function formatDate($dateTime) {
        if ($dateTime === null)
            return "";
        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * http://php.net/manual/fr/function.is-int.php#82857
     *
     * @param $input ? The input
     * @return bool true if the input is an integer, or a string that represent an integer
     */
    public function isInteger($input) {
        return ctype_digit((string) $input);
    }

    /**
     * @param $database DBController
     */
    public function setDatabase($database) {
        $this->database = $database;
    }

    /**
     * @see https://github.com/bshaffer/oauth2-server-php/blob/master/src/OAuth2/ResponseType/AuthorizationCode.php#L84
     *
     * @param $ln int The size of the random data
     *
     * @return bool|string
     */
    function generateAuthorizationCode($ln) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $randomData = openssl_random_pseudo_bytes(64);
        } elseif (function_exists('random_bytes')) {
            $randomData = random_bytes(64);
        } elseif (function_exists('mcrypt_create_iv')) {
            $randomData = mcrypt_create_iv(64, MCRYPT_DEV_URANDOM);
        } elseif (@file_exists('/dev/urandom')) { // Get 64 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 64) . uniqid(mt_rand(), true);
        } else {
            $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
        }
        return substr(hash('sha512', $randomData), 0, $ln);
    }
}

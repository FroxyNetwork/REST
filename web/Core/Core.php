<?php
/**
 * Copyright 2019 0ddlyoko
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

require_once(WEB_DIR.DS."Controller".DS."AppController.php");
require_once(WEB_DIR.DS."Controller".DS."DBController.php");
require_once(WEB_DIR.DS."Controller".DS."RequestController.php");

require_once(WEB_DIR.DS."Core".DS."Response.php");
require_once(WEB_DIR.DS."Core".DS."Route.php");

class Core {
    const MODELS = 0;
    const CONTROLLERS = 1;
    const DATASOURCES = 2;

    const _dir = [
        self::MODELS => ['model', 'Model'],
        self::CONTROLLERS => ['controller', 'Controller'],
        self::DATASOURCES => ['controller/datasourceController', 'DataController']
    ];

    /**
     * @var RequestController Le controleur de requêtes
     */
    private static $_requestController;

    /**
     * @var DBController Le controleur de données
     */
    private static $database;

    /**
     * @var array Liste des variables à ajouter aux controleurs
     */
    private static $list;

    public static function init() {
        // Initialisation des controleurs
        self::$_requestController = new RequestController();
        // TODO Récupérer les paramètres depuis un fichier de config
        self::$database = new DBController("127.0.0.1", 27017, null, null, "froxynetwork");
        self::$list = array();
        // Including Routage
        include API_DIR.DS."Config".DS."Routage.php";
        $path = self::$_requestController->getPath();
        $route = Route::getRoute($path);
        $controller = $route['controller'];
        $params = $route['param'];
        /**
         * @var $webController WebController
         */
        $webController = self::getAppController("Web");
        $webController->onLoad(self::$list);
        try {
            $appController = self::getAppController($controller);
            if ($appController == null) {
                // Erreur
                Response::error(Response::ERROR_NOTFOUND, "Non trouvé");
                return;
            }

            switch (self::$_requestController->getMethod()) {
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

        } catch (Exception $ex) {
            // Erreur
            Response::error(Response::ERROR_NOTFOUND, "Non trouvé");
        }

        //$appController->$action($params);
        $webController->onUnload();
    }

    /**
     * Récupère l'emplacement du fichier spécifié
     * Exemples:
     * Core::path(Core::MODELS, "User");
     *  ==> <...>/api/model/UserModel.php
     * Core::path(Core::CONTROLLERS, "Auth");
     *  ==> <...>/api/controller/AuthController.php
     * Core::path(Core::DATASOURCES, "User");
     *  ==> <...>/api/controller/DatasourceController/UserDataController.php
     * Core::path("TypeIncorrect", "User");
     *  ==> <...>/api/User/DatasourceController/UserDataController.php
     *
     * @param string $type Le type (MODELS, VIEWS, CONTROLLERS, DATASOURCES)
     * @param string $name Le nom du fichier
     * @return string The path of the file.
     */
    public static function path($type, $name) {
        if (isset(self::_dir[$type]) && !empty(self::_dir[$type][0]))
            return API_DIR.DS.self::_dir[$type][0].DS.self::fileName($type, $name).".php";
        return API_DIR.DS.$type.DS.self::fileName($type, $name).".php";
    }

    /**
     * Récupère le nom du fichier spécifié
     *
     * @param string $type Le type (MODELS, VIEWS, CONTROLLERS, DATASOURCES)
     * @param string $name Le nom du fichier
     * @return string Le nom du fichier (sans .php)
     */
    public static function fileName($type, $name) {
        if (!empty(self::_dir[$type][0]))
            return $name.self::_dir[$type][1];
        return $name;
    }

    /**
     * autoload classes<br />
     * Vérifie dans les dossiers "api/model", "api/controller", "api/controller/datasourceController"
     * @param $class
     * @return bool true si ok
     */
    public static function load($class) {
        if (self::endsWith($class, "Controller")) {
            $path = API_DIR.DS.self::_dir[self::CONTROLLERS][0].DS.$class.".php";
        } else if (self::endsWith($class, "Model")) {
            $path = API_DIR.DS."model".DS.$class.".php";
        } else if (self::endsWith($class, "Datasource")) {
            $path = API_DIR.DS."controller".DS."DatasourceController".DS.$class.".php";
        } else {
            $path = WEB_DIR.DS."Core".DS.$class.".php";
            if (!file_exists($path))
                return false;
        }
        if (is_file($path))
            return require $path;
        return false;
    }

    /**
     * Retourne le controleur spécifique
     * @param string $name Le nom du controleur
     * @return AppController Le contrlleur spécifique
     */
    public static function getAppController($name) {
        $file = self::fileName(self::CONTROLLERS, $name);
        /**
         * @var AppController $impl
         */
        $impl = new $file(self::$database);
        // Ajout des autres controleurs
        $impl->request = self::$_requestController;
        foreach (self::$list as $key => $value)
            $impl->$key = $value;
        return $impl;
    }

    /**
     * @param string $name Le nom de la source de données
     * @return mixed La source de données spécifique
     */
    public static function getDataController($name) {
        $file = self::fileName(self::DATASOURCES, $name);
        $impl = new $file(self::$database);
        return $impl;
    }

    static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    static function endsWith($haystack, $needle) {
        $length = strlen($needle);

        return $length === 0 ||
            (substr($haystack, -$length) === $needle);
    }
}
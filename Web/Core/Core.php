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
    private static $_requestController;

    /**
     * @var ResponseController Le controleur de réponse
     */
    private static $_responseController;

    /**
     * @var DBController Le controleur de données
     */
    private static $database;

    /**
     * @var array Liste des variables à ajouter aux controleurs
     */
    private static $list = [];

    public static function init() {
        // Initialisation des controleurs
        self::$_requestController = new RequestController();
        self::$_responseController = new ResponseController();
        // TODO Récupérer les paramètres depuis un fichier de config
        self::$database = new DBController("localhost", 3306, "root", "", "froxynetwork");
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
        $webController->onLoad(self::$list, self::$database->get());
        try {
            $appController = self::getAppController($controller);
            if ($appController == null) {
                // Erreur
                self::$_responseController->error(self::$_responseController::ERROR_NOTFOUND, "Non trouvé");
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

        } catch (\Exception $ex) {
            // Erreur
            self::$_responseController->error(self::$_responseController::ERROR_NOTFOUND, "Non trouvé");
        }

        //$appController->$action($params);
        $webController->onUnload();
        self::$database->close();
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
     * @param string $type Le type (MODELS, CONTROLLERS, DATASOURCES)
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
     * @param string $type Le type (MODELS, CONTROLLERS, DATASOURCES)
     * @param string $name Le nom du fichier
     * @return string Le nom du fichier (sans .php)
     */
    public static function fileName($type, $name) {
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
    public static function getAppController($name) {
        $file = self::fileName(self::CONTROLLERS, $name);
        /**
         * @var AppController $impl
         */
        $class = "\\Api\\Controller\\".$file;
        $impl = new $class(self::$database);
        // Ajout des autres controleurs
        $impl->request = self::$_requestController;
        $impl->response = self::$_responseController;
        foreach (self::$list as $key => $value)
            $impl->$key = $value;
        return $impl;
    }

    /**
     * // TODO Sauvegarder les anciens controleurs créés (Eviter la duplication)
     * @param string $name Le nom de la source de données
     * @return mixed La source de données spécifique
     */
    public static function getDataController($name) {
        $file = self::fileName(self::DATASOURCES, $name);
        $class = "\\Api\\Controller\\DatasourceController\\".$file;
        if (self::$database != null)
            $impl = new $class(self::$database->get());
        else
            $impl = new $class(null);
        return $impl;
    }

    static function startsWith($haystack, $needle) {
        if ($haystack === null)
            return $needle === null;
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    static function endsWith($haystack, $needle) {
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
    static function formatDate($dateTime) {
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
    static function isInteger($input) {
        return ctype_digit(strval($input));
    }

    /**
     * @param $database DBController
     */
    static function setDatabase($database) {
        self::$database = $database;
    }
}

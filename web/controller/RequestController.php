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

namespace Web\Controller;

use Web\Core\Core;

class RequestController {
    /**
     * @var bool $https True si l'on utilise https
     */
    var $https;
    /**
     * @var string $host L'hôte
     */
    var $host;
    /**
     * @var int $port Le port
     */
    var $port;
    /**
     * @var string $path Chemin (Partie de l'URL après le /)
     */
    var $path;
    /**
     * @var string file Le nom du fichier dans l'URL
     */
    var $file;
    /**
     * @var string[] $params Les paramètres (GET + POST)
     */
    var $params;
    /**
     * @var string[] $queryString Les paramètres après le ?
     */
    var $queryString;

    /**
     * RequestController constructor.
     */
    public function __construct() {
        // https ?
        $this->https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off");
        // Hôte
        $host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "localhost");
        if (strpos($host, ":") !== false)
            $host = explode(":", $host)[0];
        $this->host = $host;
        // Port
        $this->port = $_SERVER['SERVER_PORT'];
        // Chemin
        $urlpath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $path = dirname($urlpath);
        if ($path == "\\")
            $path = "/";
        $file = basename($urlpath);
        if (strpos($file, ".") === false) {
            $path .= ((Core::endsWith($path, "/")) ? "" : "/").$file;
            $file = "";
        }
        $this->path = $path;
        $this->file = $file;
        // Changement de $_REQUEST
        $this->params = $_REQUEST = array_merge($_POST, $_GET);
        $this->queryString = $_SERVER["QUERY_STRING"];
    }

    /**
     * @return string La méthode utilisée (GET, POST, ...)
     */
    function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return bool true si la requête est en HTTPS
     */
    function isHttps() {
        return $this->https;
    }

    /**
     * @return string L'hôte
     */
    function getHost() {
        return $this->host;
    }

    /**
     * @return int Le port
     */
    function getPort() {
        return $this->port;
    }

    /**
     * @return string Le chemin
     */
    function getPath() {
        return $this->path;
    }

    /**
     * @return string Le nom du fichier
     */
    function getFile() {
        return $this->file;
    }

    /**
     * @return string[] Paramètres après le ?
     */
    function getQueryString() {
        return $this->queryString;
    }

    /**
     * @param bool $http True pour ajouter http(s)://
     * @param bool $param True pour ajouter les paramètres $_SERVER["QUERY_STRING"]
     * @return string Retourne l'URL actuelle
     */
    function getURL($http = false, $param = false) {
        return (($http) ? "http".(($this->https) ? "s" : "")."://" : "").$this->getHost().(($this->port != 80 && $this->port != 443) ? ":".$this->port : "").$this->getPath()."/".($this->getFile()).(($param && !empty($this->queryString)) ? "?".$this->queryString : "");
    }

    /**
     * @param string $param Le paramètre
     * @return string L'id du paramètre
     */
    function get($param) {
        return $this->params[$param];
    }

    /**
     * @return string[] Return tout les paramètres
     */
    function getAll() {
        return $this->params;
    }

    function isAJAX() {
        return array_key_exists("HTTP_TOKEN", $_SERVER) && !empty($_SERVER['HTTP_TOKEN']);
    }
}
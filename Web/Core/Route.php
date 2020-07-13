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

class Route {
    /**
     * Structure:
    [
        {
            route: "/login",
            controller: "user"
        },
        {
            route: "/profile",
            controller: "profile"
        }
    ]
     * @var array $routes Les routes.
     */
    private static $routes = array();

    private static $path = "/";

    /**
     * Lier une URL à un Controlleur
     *
     * Route::connect('URL', 'nom du controlleur');
     *
     * Exemples:
     * Route::connect('profile', 'profile');
     *
     * @param $url string L'URL
     * @param $to string le controlleur (Sans "Controller" à la fin)
     */
    public static function connect($url, $to) {
        if (self::$path != "/")
            $url = self::$path.$url;
        self::$routes[] = array(
            "route" => $url,
            "controller" => $to
        );
    }

    /**
     * Retourne le controlleur associé à une URL
     * L'URL est comme: "/login", "/register", "/", "/game/play" ...
     * @param string $url L'URL
     * @return array Retourne une liste contenant par exemple:
     * GET /login
     *
     * {
     *      controller: "Login",
     *      params: ""
     * }
     * GET /login/0ddlyoko
     *
     * {
     *      controller: "Login",
     *      params: "/0ddlyoko"
     * }
     * GET /login/0ddlyoko/id
     *
     * {
     *      controller: "Login",
     *      params: "/0ddlyoko/id"
     * }
     */
    public static function getRoute($url) {
        $result = array();
        // On inverse la route
        $routes = array_reverse(self::$routes);
        // Pour chaques routes
        foreach($routes as $r) {
            $i = 0;
            $rExplode = explode("/", $r['route']);
            if (self::$path != "/" && $rExplode[count($rExplode) - 1] == "")
                unset($rExplode[count($rExplode) - 1]);
            $urlExplode = explode("/", $url);
            if ($r['route'] != "/") {
                // Si la taille de $url est plus petite que la taille de la route, ce n'est pas celui-là
                if (count($urlExplode) < count($rExplode))
                    continue;
                // Pour chaques paramètres dans la route, nous vérifions si c'est le bon
                $correct = true;
                for (; $i < count($rExplode) && $correct; $i++)
                    if (strtolower($rExplode[$i]) != strtolower($urlExplode[$i]))
                        $correct = false;
                if (!$correct)
                    continue;
            }
            $param = "";
            for (; $i < count($urlExplode); $i++)
                if (!empty($urlExplode[$i]))
                  $param .= "/".$urlExplode[$i];
            $result['controller'] = $r['controller'];
            $result['param'] = $param;
            break;
        }
        return $result;
    }

    /**
     * Configure the path of the route.
     * Example:
     * The REST server is in url localhost/api, so we'll call the configure function with "/api" for the path
     * @param $path string The path
     */
    public static function configure($path) {
        self::$path = $path;
    }
}
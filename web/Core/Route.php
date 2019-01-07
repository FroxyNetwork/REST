<?php
/**
 * This file is part of REST.
 *
 * REST is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * REST is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with REST.  If not, see <https://www.gnu.org/licenses/>.
 */
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
            $urlExplode = explode("/", $url);
            if ($r['route'] != "/") {
                // Si la taille de $url est plus petite que la taille de la route, ce n'est pas celui-là
                if (count($urlExplode) < count($rExplode))
                    continue;
                // Pour chaques paramètres dans la route, nous vérifions si c'est le bon
                $correct = true;
                for (; $i < count($rExplode) && $correct; $i++)
                    if ($rExplode[$i] != $urlExplode[$i])
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
}
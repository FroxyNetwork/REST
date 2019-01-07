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
class TokenController extends AppController {

    function load() {
        // TODO Compatibilité PHP versions
        if (empty($_SESSION['token']))
            $_SESSION['token'] = bin2hex(random_bytes(32));
    }

    /**
     * Retourne le token actuel.
     * Same as $_SESSION['token']
     */
    function get($param = "") {
        return $_SESSION['token'];
    }

    /**
     * @return bool Vérifie si le token existe et est correct
     */
    function check() {
        if ($this->request->isAJAX()) {
            $token = $_SERVER['HTTP_TOKEN'];
            return hash_equals($_SESSION['token'], $token);
        }
    }

    /**
     * Token invalide
     */
    function invalid() {
        // Token invalide
        Response::error(Response::ERROR_FORBIDDEN, "Invalid Token, Please save your informations and refresh your page !");
    }
}
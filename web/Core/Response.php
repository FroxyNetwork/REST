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
final class Response {
    const SUCCESS_OK = 200;
    const SUCCESS_CREATED = 201;
    const SUCCESS_ACCEPTED = 202;
    const SUCCESS_NO_CONTENT = 204;

    const REDIRECT_PERMANENTLY = 301;
    const REDIRECT_FOUND = 302;
    const REDIRECT_NOT_MODIFIED = 304;
    const REDIRECT_TEMPORARY = 307;

    const ERROR_BAD_REQUEST = 400;
    const ERROR_UNAUTHORIZED = 401;
    const ERROR_FORBIDDEN = 403;
    const ERROR_NOTFOUND = 404;

    const SERVER_INTERNAL = 500;
    const SERVER_NOT_IMPLEMENTED = 501;

    private function __construct() {}

    public static function response($data, $code = 200, $error = false) {
        return [
            "error" => $error,
            "code" => $code,
            "data" => $data
        ];
    }

    /**
     * Envoyer un message d'erreur
     *
     * @param int errorCode Le code d'erreur
     * @param string error Le message d'erreur
     */
    public static function error($errorCode, $error) {
        self::send(self::response($error, $errorCode, true));
    }

    /**
     * Envoyer un message d'erreur "NOT_IMPLEMENTED"
     */
    public static function notImplemented() {
        self::error(self::SERVER_NOT_IMPLEMENTED, "This method is not implemented");
    }

    /**
     * Envoyer un message au format json
     *
     * @param $data array Les données à afficher
     */
    public static function send($data) {
        header('Content-Type:application/json');
        if (isset($data['code']))
            http_response_code($data['code']);
        echo json_encode($data);
    }
}
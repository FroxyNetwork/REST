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

    private static function _create($data, $code = 200, $error = false, $error_message = null) {
        return [
            "error" => $error,
            "code" => $code,
            "error_message" => $error_message,
            "data" => $data
        ];
    }

    /**
     * Envoyer un message sous format JSON
     *
     * @param ? $data Les données à envoyer
     * @param int $code Le code de retour
     */
    public static function ok($data, $code = 200) {
        self::send(self::_create($data, $code));
    }

    /**
     * Envoyer un message d'erreur
     *
     * @param int errorCode Le code d'erreur
     * @param string error Le message d'erreur
     *
     * TODO: Custom Error ID
     */
    public static function error($errorCode, $error) {
        self::send(self::_create(null, $errorCode, true, $error));
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
    private static function send($data) {
        header('Content-Type:application/json');
        if (isset($data['code']))
            http_response_code($data['code']);
        echo json_encode($data);
    }
}
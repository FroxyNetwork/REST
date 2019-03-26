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
 *
 * @author 0ddlyoko
 */

namespace Web\Controller;

class ResponseController {
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
    const ERROR_CONFLICT = 409;

    const SERVER_INTERNAL = 500;
    const SERVER_NOT_IMPLEMENTED = 501;

    public static function _create($data, $code = 200, $error = false, $error_message = null) {
        if (!is_int($code))
            throw new \InvalidArgumentException("Code must be a number");
        if (!is_bool($error))
            throw new \InvalidArgumentException("Error must be a boolean");
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
    public function ok($data, $code = 200) {
        if (!is_int($code))
            throw new \InvalidArgumentException("Code must be a number");
        $this->send(self::_create($data, $code));
    }

    /**
     * Envoyer un message d'erreur
     *
     * @param int errorCode Le code d'erreur
     * @param string error Le message d'erreur
     *
     * TODO: Custom Error ID
     */
    public function error($errorCode, $error) {
        if (!is_int($errorCode))
            throw new \InvalidArgumentException("ErrorCode must be a number");
        $this->send(self::_create(null, $errorCode, true, $error));
    }

    /**
     * Envoyer un message d'erreur "NOT_IMPLEMENTED"
     */
    public function notImplemented() {
        $this->error(self::SERVER_NOT_IMPLEMENTED, "This method is not implemented");
    }

    /**
     * Envoyer un message au format json
     *
     * @param $data array Les données à afficher
     */
    protected function send($data) {
        header('Content-Type:application/json');
        if (isset($data['code']))
            http_response_code($data['code']);
        echo json_encode($data);
    }
}
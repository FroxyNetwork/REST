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

use Web\Core\Error;

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

    /**
     * Crée un message en JSON qui sera envoyé
     *
     * @param string $data Les données à envoyer
     * @param int $code Le code de retour
     * @param array $error L'erreur. Null par défaut
     * @return array
     */
    public static function _create($data, $code = 200, $error = null) {
        if (!is_int($code))
            throw new \InvalidArgumentException("Code must be a number");
        if ($error !== null) {
            if (!is_array($error))
                throw new \InvalidArgumentException('Error must be a correct array ! See \Api\Model\Error class');
            if (!is_int($error[0]))
                throw new \InvalidArgumentException('$error[0] must be an integer ! See \Api\Model\Error class');
            if (!is_string($error[1]))
                throw new \InvalidArgumentException('$error[1] must be a string ! See \Api\Model\Error class');
        }
        $result = [
            "error" => $error !== null,
            "code" => $code,
            "data" => $data
        ];
        if ($error !== null) {
            $result["error_id"] = $error[0];
            $result["error_message"] = $error[1];
        }
        return $result;
    }

    /**
     * Envoyer un message sous format JSON
     *
     * @param ? $data Les données à envoyer. Si data vaut null, alors on objet vide sera envoyé ("{}")
     * @param int $code Le code de retour
     */
    public function ok($data = null, $code = 200) {
        if (is_null($data)) {
            // Data is null, so it'll be an empty object (to return "{}")
            $data = new \stdClass();
        }
        if (!is_int($code))
            throw new \InvalidArgumentException("Code must be a number");
        $this->send(self::_create($data, $code));
    }

    /**
     * Envoyer un message d'erreur <br />
     * Exemple:
     * <code>
     * error(Error::GLOBAL_ERROR, ["errorCode" => "1", "error" => "An error has occured: XXX"]);
     * </code>
     *
     * @param int $errorCore Le code d'erreur (400, 401, ...)
     * @param array $error L'erreur
     * @param array $args Les arguments
     *
     * @see \Api\Model\Error
     */
    public function error($errorCore, $error, $args = []) {
        if (!is_int($errorCore))
            throw new \InvalidArgumentException('$errorCore must be an integer ! See \Web\Controller\ResponseController class');
        if (!is_array($error))
            throw new \InvalidArgumentException('Error must be a correct array ! See \Api\Model\Error class');
        if (!is_int($error[0]))
            throw new \InvalidArgumentException('$error[0] must be an integer ! See \Api\Model\Error class');
        if (!is_string($error[1]))
            throw new \InvalidArgumentException('$error[1] must be a string ! See \Api\Model\Error class');
        $msg = $error[1];
        foreach ($args as $key => $value)
            $msg = str_replace("{".$key."}", $value, $msg);
        $error_2 = [];
        $error_2[0] = $error[0];
        $error_2[1] = $msg;
        $this->send(self::_create(null, $errorCore, $error_2));
    }

    /**
     * Envoyer un message d'erreur "NOT_IMPLEMENTED"
     */
    public function notImplemented() {
        $this->error(self::SERVER_NOT_IMPLEMENTED, Error::METHOD_NOT_IMPLEMENTED);
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
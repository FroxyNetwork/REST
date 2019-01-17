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

use PDO;
use PDOException;

class DBController {
    /**
     * @var string $host L'hote
     */
    private $host;
    /**
     * @var string $port Le port
     */
    private $port;
    /**
     * @var string $login Le login
     */
    private $login;
    /**
     * @var string $password Le mot de passe
     */
    private $password;
    /**
     * @var string $dbName Le nom de la bdd
     */
    private $dbName;
    /**
     * @var PDO $db L'objet PDO
     */
    private $db;

    /**
     * DBController constructor.
     *
     * @param $host
     * @param $port
     * @param $login
     * @param $password
     * @param $dbName
     */
    function __construct($host, $port, $login, $password, $dbName) {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->dbName = $dbName;
    }

    function get() {
        if (!empty($this->db))
            return $this->db;
        try {
            $strConnection = 'mysql:host='.$this->host.';dbname='.$this->dbName.';port='.$this->port;
            $this->db = new PDO($strConnection, $this->login, $this->password);
            $this->db->exec("SET CHARACTER SET utf8");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $ex) {
            $msg = 'ERREUR PDO dans ' . $ex->getFile() . ' Ligne : ' . $ex->getLine() . ' : ' . $ex->getMessage();
            die ($msg);
        }
        return $this->db;
    }
}
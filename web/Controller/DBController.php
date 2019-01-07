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
     * @var \MongoDB\Database $db L'objet MongoDB
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
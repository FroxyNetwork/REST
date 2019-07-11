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

use MongoDB\Client;

class DBController {
    /**
     * @var string $url L'url
     */
    private $url;
    /**
     * @var string $dbName Le nom de la base de données
     */
    private $dbName;
    /**
     * @var Client $db L'objet Client
     */
    private $db;

    /**
     * DBController constructor.
     *
     * @param $url
     * @param $dbName
     */
    function __construct($url, $dbName) {
        $this->url = $url;
        $this->dbName = $dbName;
    }

    function getClient() {
        return $this->db;
    }

    function get($collection) {
        if (empty($this->db)) {
            $this->db = new Client($this->url);
            $this->db->listDatabases();
        }
        $dbName = $this->dbName;
        return $this->db->$dbName->selectCollection($collection);
    }

    function close() {
        $this->db = null;
    }
}
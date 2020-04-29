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


namespace Api\Controller\DatasourceController;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Web\Controller\DBController;

class ServertesterDataController {
    /**
     * @var Collection $db
     */
    private $db;

    public function __construct(DBController $db) {
        $this->db = $db->get("servertester");
    }

    function get($id) {
        try {
            $c = $this->db->findOne(["_id" => $id]);
            if (empty($c) || !$c)
                return false;
            $result = [
                'id' => $c['_id'],
                'token' => $c['token']
            ];
            if (isset($c['client_id']) && !empty($c['client_id']))
                $result['client_id'] = $c['client_id'];
            return $result;
        } catch (\Exception $ex) {
            return false;
        }
    }

    function create($id, $token, $clientId = null) {
        try {
            $id = [
                'id' => $id,
                'token' => $token
            ];
            if ($clientId != null)
                $id['client_id'] = $clientId;
            $c = $this->db->updateOne(['_id' => $id['id']], ['$set' => ['_id' => $id['id'], 'token' => $id['token']]], ['upsert' => true]);
            return $id;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param $id int The id
     *
     * @return bool True if deleted
     */
    public function delete($id) {
        try {
            $c = $this->db->deleteOne(['_id' => $id]);
            return $c->getDeletedCount() == 1;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
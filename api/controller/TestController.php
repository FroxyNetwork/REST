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
class TestController extends AppController {


    public function get($param) {
        Response::send(["Token" => $this->token->get()]);
    }

    public function post($param) {
        Response::send(["METHODE" => "POST"]);
    }

    public function put($param) {
        Response::send(["METHODE" => "PUT"]);
    }

    public function delete($param) {
        Response::send(["METHODE" => "DELETE"]);
    }
}
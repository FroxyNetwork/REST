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
abstract class AppController {

    /**
     * @var RequestController The Request Controller
     */
    public $request;

    public function __construct() {
    }

    public function get($param) {
        Response::notImplemented();
    }

    public function post($param) {
        Response::notImplemented();
    }

    public function put($param) {
        Response::notImplemented();
    }

    public function delete($param) {
        Response::notImplemented();
    }

    public function head($param) {
        Response::notImplemented();
    }

    public function options($param) {
        Response::notImplemented();
    }

    // Autre chose
    public function other($param) {
        Response::notImplemented();
    }
}
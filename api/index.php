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

if (!defined('DS'))
    define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT'))
    define('ROOT', dirname(dirname(__FILE__)));
if (!defined('API_NAME'))
    define('API_NAME', 'api');
if (!defined('API_DIR'))
    define('API_DIR', ROOT.DS.API_NAME);
if (!defined('WEB_NAME'))
    define('WEB_NAME', 'web');
if (!defined('WEB_DIR'))
    define('WEB_DIR', ROOT.DS.WEB_NAME);
// TODO Récupérer l'url depuis un fichier de config
if (!defined('WEBSITE'))
    define('WEBSITE', 'localhost');

session_start();
require WEB_DIR.DS."Core".DS."Core.php";
spl_autoload_register(['Core', 'load']);
Core::init();

//require("webroot".DIRECTORY_SEPARATOR."index.php");
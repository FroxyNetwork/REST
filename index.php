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

/*
 * Ce fichier est executé parce que mod_rewrite ou .htaccess sont désactivés
 */

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('API_NAME', 'api');
define('API_DIR', ROOT.DS.API_NAME);
define('WEB_NAME', 'web');
define('WEB_DIR', ROOT.DS.WEB_NAME);
// TODO Récupérer l'url depuis un fichier de config
define('WEBSITE', 'localhost');

$redirectNotSupported = true;

require API_DIR.DS."index.php";
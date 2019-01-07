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
class WebController extends AppController {

    /**
     * Méthode appelée quand l'app charge
     * Utilisé pour charger les utilisateurs, etc
     * Exemple de structure pour $list:
     * [
     *      "user" : $user
     * ]
     * Donc, pour tout les controleurs, il y aura une variable $user avec comme valeur user
     *
     * @param array $list Une liste de chose à ajouter aux controleurs
     */
    function onLoad(array &$list) {
        $this->loadToken($list);
    }

    function loadToken(array &$list) {
        /**
         * @var TokenController $tokenController
         */
        $tokenController = Core::getAppController("Token");
        $tokenController->load();
        $list['token'] = $tokenController;
    }

    /**
     * Méthode appelée quand l'app décharge
     */
    function onUnload() {

    }
}
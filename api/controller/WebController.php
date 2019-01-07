<?php
/**
 * Copyright 2019 0ddlyoko
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
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
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

namespace Api\Controller;

use Api\Controller\DatasourceController\PlayerDataController;
use Web\Controller\AppController;
use Web\Core\Core;
use Web\Core\Response;

class PlayerController extends AppController {

    /**
     * @var PlayerDataController
     */
    private $playerDataController;

    public function __construct() {
        parent::__construct();
        $this->playerDataController = Core::getDataController("Player");
    }

    public function get($param) {
        if (Core::startsWith($param, "/"))
            $param = substr($param, 1);
        $ln = strlen($param);
        $player = null;
        if ($ln == 36) {
            // UUID
            $player = $this->playerDataController->getUserByUUID($param);
        } else if ($ln >= 1 && $ln <= 20) {
            // Name
            $player = $this->playerDataController->getUserByPseudo($param);
        } else {
            Response::error(Response::ERROR_BAD_REQUEST, "The length of the search must be between 1 and 20, or equals to 36.");
            return;
        }
        if (!empty($GLOBALS['errorCode'])) {
            if ($GLOBALS['errorCode'] == PlayerDataController::ERROR_NOT_FOUND) {
                // Player not found
                Response::error(Response::ERROR_NOTFOUND, "Player not found");
                return;
            }
            // Error
            Response::error(Response::ERROR_BAD_REQUEST, "Error #".$GLOBALS['errorCode']." : ".$GLOBALS['error']);
            return;
        } else if ($player == null) {
            // Unknown error
            Response::error(Response::ERROR_BAD_REQUEST, "Unknown error");
            return;
        }
        Response::ok([
            "uuid" => $player->getUuid(),
            "pseudo" => $player->getPseudo(),
            "displayName" => "<HIDDEN>",// TODO Allow only for special ranks
            "coins" => $player->getCoins(),
            "level" => $player->getLevel(),
            "exp" => $player->getExp(),
            "firstLogin" => $player->getFirstLogin()->format('Y-m-d H:i:s'),
            "lastLogin" => $player->getLastLogin()->format('Y-m-d H:i:s'),
            "ip" => "<HIDDEN>",// TODO Allow only for special ranks
            "lang" => $player->getLang()
        ]);
    }
}
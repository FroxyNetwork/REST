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
 *
 * @author 0ddlyoko
 */
namespace Web\Core;
use Api\Model\ServerStatus;
interface Error {
    const METHOD_NOT_IMPLEMENTED = [0, "This method is not implemented"];
    const GLOBAL_ERROR = [1, "Error #{errorCode} : {error}"];
    const GLOBAL_UNKNOWN = [2, "Unknown error"];
    const GLOBAL_NO_PERMISSION = [3, "You don't have the permission to perform this operation"];
    const GLOBAL_DATA_INVALID = [4, "Invalid data value !"];
    const GLOBAL_CONTROLLER_NOT_FOUND = [5, "No controller found for your request !"];
    const GLOBAL_UNKNOWN_ERROR = [6, "An error has occured while performing the operation"];
    const GLOBAL_TOKEN_INVALID = [7, "Invalid Token, Please save your informations and refresh your page !"];
    const ROUTE_NOT_FOUND = [8, "This route doesn't exist"];

    const PLAYER_DATA_LENGTH = [100, "The length of the search must be between 1 and 20, or equals to 36."];
    const PLAYER_NOT_FOUND = [101, "Player not found"];
    const PLAYER_UUID_LENGTH = [102, "UUID length must be 36 !"];
    const PLAYER_UUID_FORMAT = [103, "Bad UUID format !"];
    const PLAYER_PSEUDO_LENGTH = [104, "Pseudo length must be between 1 and 20 !"];
    const PLAYER_IP_LENGTH = [105, "Ip length must be between 7 and 15 !"];
    const PLAYER_IP_FORMAT = [106, "Bad ip format !"];
    const PLAYER_UUID_EXISTS = [107, "UUID already exist !"];
    const PLAYER_PSEUDO_EXISTS = [108, "Pseudo already exist !"];
    const PLAYER_DISPLAYNAME_LENGTH = [109, "DisplayName length must be between 1 and 20 !"];
    const PLAYER_COINS_POSITIVE = [110, "Coins must be positive !"];
    const PLAYER_LEVEL_POSITIVE = [111, "Level must be positive !"];
    const PLAYER_EXP_POSITIVE = [112, "Exp must be positive !"];
    const PLAYER_TIME_FORMAT = [113, "Bad time format !"];
    const PLAYER_LASTLOGIN_GREATER = [114, "LastLogin must be greater than saved LastLogin !"];
    const PLAYER_SERVER_INVALID = [115, "Invalid Server type !"];
    const PLAYER_SERVER_NOT_FOUND = [116, "Server not found !"];
    const PLAYER_SERVER_NOT_OPENED = [117, "Server isn't opened !"];

    const SERVER_ID_INVALID = [200, "Invalid id"];
    const SERVER_NOT_FOUND = [201, "Server not found"];
    const SERVER_NAME_INVALID = [202, "Name must be a string !"];
    const SERVER_NAME_LENGTH = [203, "Name length must be between 1 and 16 !"];
    const SERVER_PORT_INVALID = [204, "Port must be a correct number and between 1 and 65535 !"];
    const SERVER_SAVING = [205, "Error while saving client_id and client_secret"];
    const SERVER_STATUS_INVALID = [206, "Status must be '" . ServerStatus::WAITING . "', '" . ServerStatus::STARTED . "' or '" . ServerStatus::ENDING . "' !"];
    const SERVER_STATUS_BEFORE = [207, "Invalid status, current is {currentStatus} !"];
    const SERVER_ACTUALLY_ENDED = [208, "This server is already ended !"];
    const SERVER_TYPE_INVALID = [209, "Type must be a string !"];
    const SERVER_TYPE_LENGTH = [210, "Type length must be between 1 and 16 !"];
    const SERVER_TESTER_INVALID = [211, "Invalid id / token / client_id"];
    const SERVER_SERVER_INVALID = [212, "Invalid server !"];
    const SERVER_SERVER_DOCKER_INVALID = [213, "Invalid docker id !"];
    const SERVER_SERVER_ALREADY_ID = [214, "This server is already linked to a docker"];
    const SERVER_DOCKER_SAVING = [215, "Error while saving docker configuration"];

    const INTERNAL_SERVER_JSON = [1000, "Internal Server Error: servers.json is not a valid json file !"];
    const INTERNAL_SERVER_CONFIG = [1001, "Internal Server Error: config.ini is not a valid ini file !"];
    const INTERNAL_SERVER_CONFIG_MONGODB = [1002, "Internal Server Error: config.ini is not a valid ini file !"];
    const INTERNAL_SERVER_DATABASE = [1003, "Internal Server Error: Cannot contact database !"];
    const SERVER_TYPE_NOT_FOUND = [1004, "Server type not found !"];
}

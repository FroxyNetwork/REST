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

/*
 * This file will generate a random client ID and client SECRET used for the WebSocket.
 * Once it's created:
 * - go to your mongodb database
 * - execute the code that is prompted on your screen
 */


/**
 * Generate a client_id and a client_secret (Used for Java servers)
 *
 * @return array Client and Secret
 */
function generateClientSecret($prefix) {
    $client = $prefix . generateAuthorizationCode(32);
    $secret = "SECRET_" . generateAuthorizationCode(32);
    return [$client, $secret];
}

function generateAuthorizationCode($ln) {
    if (function_exists('random_bytes')) {
        $randomData = random_bytes(64);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $randomData = openssl_random_pseudo_bytes(64);
    } elseif (function_exists('mcrypt_create_iv')) {
        $randomData = mcrypt_create_iv(64, MCRYPT_DEV_URANDOM);
    } elseif (@file_exists('/dev/urandom')) { // Get 64 bytes of random data
        $randomData = file_get_contents('/dev/urandom', false, null, 0, 64) . uniqid(mt_rand(), true);
    } else {
        $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
    }
    return substr(hash('sha512', $randomData), 0, $ln);
}

$websocket = generateClientSecret('WEBSOCKET_');
$bungee = generateClientSecret('BUNGEE_');

echo "db.oauth_clients.insert({
    client_id: \"${websocket[0]}\",
    client_secret: \"${websocket[1]}\",
    redirect_uri: null,
    grant_types: null,
    scope: \"server_show_port server_create player_create player_show_realname player_show_ip websocket_check_token server_download\",
    user_id: null
});";

echo "db.oauth_clients.insert({
    client_id: \"${bungee[0]}\",
    client_secret: \"${bungee[1]}\",
    redirect_uri: null,
    grant_types: null,
    scope: \"server_show_port player_create player_show_realname player_show_ip websocket_connection\",
    user_id: null
});";
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

namespace Api\Model;

/**
 * Class PlayerModel Player's class (in-game)
 */
class PlayerModel {
    /**
     * @var string UUID of player
     */
    private $uuid;
    /**
     * @var string real name
     */
    private $pseudo;
    /**
     * @var string display name
     */
    private $displayName;
    /**
     * @var int coins number
     */
    private $coins;
    /**
     * @var int level
     */
    private $level;
    /**
     * @var int exp
     */
    private $exp;
    /**
     * @var \DateTime first login
     */
    private $firstLogin;
    /**
     * @var \DateTime last login
     */
    private $lastLogin;
    /**
     * @var string last ip
     */
    private $ip;
    /**
     * @var string player's language
     */
    private $lang;

    /**
     * PlayerModel constructor.
     * @param $uuid
     * @param $pseudo
     * @param $displayName
     * @param $coins
     * @param $level
     * @param $exp
     * @param $firstLogin
     * @param $lastLogin
     * @param $ip
     * @param $lang
     */
    public function __construct(string $uuid, string $pseudo, string $displayName, int $coins, int $level, int $exp, \DateTime $firstLogin, \DateTime $lastLogin, string $ip, string $lang) {
        $this->uuid = $uuid;
        $this->pseudo = $pseudo;
        $this->displayName = $displayName;
        $this->coins = $coins;
        $this->level = $level;
        $this->exp = $exp;
        $this->firstLogin = $firstLogin;
        $this->lastLogin = $lastLogin;
        $this->ip = $ip;
        $this->lang = $lang;
    }

    /**
     * @return string
     */
    public function getUuid(): string {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getPseudo(): string {
        return $this->pseudo;
    }

    /**
     * @param string $pseudo
     */
    public function setPseudo(string $pseudo) {
        $this->pseudo = $pseudo;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName) {
        $this->displayName = $displayName;
    }

    /**
     * @return int
     */
    public function getCoins(): int {
        return $this->coins;
    }

    /**
     * @param int $coins
     */
    public function setCoins(int $coins) {
        $this->coins = $coins;
    }

    /**
     * @return int
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level) {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getExp(): int {
        return $this->exp;
    }

    /**
     * @param int $exp
     */
    public function setExp(int $exp) {
        $this->exp = $exp;
    }

    /**
     * @return \DateTime
     */
    public function getFirstLogin(): \DateTime {
        return $this->firstLogin;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin(): \DateTime {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     */
    public function setLastLogin(\DateTime $lastLogin) {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string
     */
    public function getIp(): string {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip) {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getLang(): string {
        return $this->lang;
    }

    /**
     * @param string $lang
     */
    public function setLang(string $lang) {
        $this->lang = $lang;
    }
}
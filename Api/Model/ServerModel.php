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

namespace Api\Model;

/**
 * Class ServerModel A server
 */
class ServerModel {
    /**
     * @var int $id The id
     */
    private $id;
    /**
     * @var string $name The name of the server
     */
    private $name;
    /**
     * @var int $port The port of the server
     */
    private $port;
    /**
     * @var string $status The status of the server
     */
    private $status;
    /**
     * @var \DateTime $creationTime The creation date
     */
    private $creationTime;
    /**
     * @var string $scope The scope (User for OAuth2)
     */
    private $scope;

    /**
     * ServerModel constructor.
     * @param int $id
     * @param string $name
     * @param int $port
     * @param \DateTime $creationTime
     * @param string $status
     * @param string $scope
     */
    public function __construct(int $id, string $name, int $port, string $status, \DateTime $creationTime, string $scope = "") {
        $this->id = $id;
        $this->name = $name;
        $this->port = $port;
        $this->status = $status;
        $this->creationTime = $creationTime;
        $this->scope = $scope;
    }

    /**
     * @return int The id
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id The id
     */
    public function setId(int $id) {
        $this->id = $id;
    }

    /**
     * @return string The name of the server
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return int The port of the server
     */
    public function getPort(): int {
        return $this->port;
    }

    /**
     * @return ServerStatus The status of the server
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * @param string $status The status of the server
     */
    public function setStatus(string $status) {
        $this->status = $status;
    }

    /**
     * @return \DateTime The creation time of the server
     */
    public function getCreationTime() : \DateTime {
        return $this->creationTime;
    }

    /**
     * @return string The scope (User for OAuth2)
     */
    public function getScope() : string {
        return $this->scope;
    }
}
<?php

namespace Oro\Bundle\SyncBundle\WebSocket\Client;

use Oro\Bundle\SyncBundle\Exception\WebSocket;

class Rfc6455
{
    /**
     * Default socket connection timeout, in seconds
     */
    const SOCKET_TIMEOUT = 2;
    const SERVER_HANDSHAKE_STATUS_LINE = 'HTTP/1.1 101 Switching Protocols';

    /** @var resource */
    protected $socket;

    /** @var string */
    protected $remoteSocketAddress;

    /** @var bool */
    protected $blocking = false;

    /**
     * @param string $host
     * @param string|integer $port
     * @param string $path
     * @return resource WebSocket stream resource
     * @throws WebSocket\Rfc6455Exception
     */
    public function connect($host, $port, $path = '')
    {
        $path = trim($path, '/');

        $remoteSocket = "tcp://{$host}:{$port}/{$path}";

        if ($this->remoteSocketAddress === $remoteSocket && $this->socket) {
            return $this->socket;
        }

        $this->remoteSocketAddress = $remoteSocket;

        $this->socket = $this->create();

        $headers = $this->getHeaders($host, $port, $path);

        $this->handshake($headers);

        $set_blocking = stream_set_blocking($this->socket, $this->blocking);

        if (false === $set_blocking) {
            throw new WebSocket\SetupFailure(
                sprintf(
                    'WebSocket set %sblocking stream socket failure. Can not set blocking to %s',
                    $this->blocking ? '' : 'non-',
                    var_export($this->blocking, 1)
                )
            );
        }

        return $this->socket;
    }

    /**
     * @param string $host
     * @param string|int $port
     * @param string $path
     * @return string
     */
    protected function getHeaders($host, $port, $path)
    {
        $webSocketKey = base64_encode($this->generateRandomString(16));

        $header = "GET /{$path} HTTP/1.1\r\n";
        $header .= "Host: {$host}:{$port}\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Connection: Upgrade\r\n";
        $header .= "Sec-WebSocket-Key:  {$webSocketKey}\r\n";
        $header .= "Sec-WebSocket-Version: 13\r\n";
        $header .= "\r\n";

        return $header;
    }

    /**
     * Disconnect
     */
    public function disconnect()
    {
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }

    /**
     * Hoa
     *
     *
     * @license
     *
     * New BSD License
     *
     * Copyright © 2007-2015, Hoa community. All rights reserved.
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions are met:
     *     * Redistributions of source code must retain the above copyright
     *       notice, this list of conditions and the following disclaimer.
     *     * Redistributions in binary form must reproduce the above copyright
     *       notice, this list of conditions and the following disclaimer in the
     *       documentation and/or other materials provided with the distribution.
     *     * Neither the name of the Hoa nor the names of its contributors may be
     *       used to endorse or promote products derived from this software without
     *       specific prior written permission.
     *
     * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
     * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
     * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
     * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
     * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
     * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
     * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
     * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
     * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
     * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
     * POSSIBILITY OF SUCH DAMAGE.
     *
     * ============
     * Link to the original method:
     * https://github.com/hoaproject/Websocket/blob/bec469034ab9da8d09368f5e4d7e86b395f2af03/Protocol/Rfc6455.php#L226
     *
     * @param   string $message Message.
     * @param   int $opcode Opcode.
     * @param   bool $end Whether it is the last frame of the message.
     * @param   bool $mask Whether the message will be masked or not.
     *
     * @return string
     */
    public function createFrame($message, $opcode = 0x1, $end = true, $mask = true)
    {
        $fin = true === $end ? 0x1 : 0x0;
        $rsv1 = 0x0;
        $rsv2 = 0x0;
        $rsv3 = 0x0;
        $mask = true === $mask ? 0x1 : 0x0;
        $length = strlen($message);
        $out = chr(
            ($fin << 7) | ($rsv1 << 6) | ($rsv2 << 5) | ($rsv3 << 4) | $opcode
        );

        if (0xffff < $length) {
            $out .= chr(($mask << 7) | 0x7f) . pack('NN', 0, $length);
        } elseif (0x7d < $length) {
            $out .= chr(($mask << 7) | 0x7e) . pack('n', $length);
        } else {
            $out .= chr(($mask << 7) | $length);
        }

        return $out . $this->maskMessage($message, $mask);
    }

    /**
     * For the original method:
     * @see https://github.com/hoaproject/Websocket/blob/bec469034ab9da8d09368f5e4d7e86b395f2af03/Protocol/Rfc6455.php#L226
     *
     * @param string $message
     * @param bool $mask
     *
     * @return string
     */
    protected function maskMessage($message, $mask)
    {
        if (0x0 === $mask) {
            return $message;
        }

        $maskingKey = array_map('ord', str_split(random_bytes(4)));

        for ($i = 0, $max = strlen($message); $i < $max; ++$i) {
            $message[$i] = chr(ord($message[$i]) ^ $maskingKey[$i % 4]);
        }

        return implode('', array_map('chr', $maskingKey)) . $message;
    }

    /**
     * Generate random string for a WebSocket handshake request headers
     *
     * @param  int $length
     * @param  bool $addSpaces
     * @param  bool $addNumbers
     * @return string
     */
    protected function generateRandomString($length = 10, $addSpaces = true, $addNumbers = true)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $useChars = [];

        // select some random chars:
        for ($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[random_int(0, strlen($characters) - 1)];
        }

        // add spaces and numbers:
        if ($addSpaces === true) {
            array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
        }

        if ($addNumbers === true) {
            array_push($useChars, random_int(0, 9), random_int(0, 9), random_int(0, 9));
        }

        shuffle($useChars);

        $randomString = trim(implode('', $useChars));

        return substr($randomString, 0, $length);
    }

    /**
     * Creates stream socket client resource
     * @throws WebSocket\SetupFailure|WebSocket\ConnectionError
     */
    protected function create()
    {
        $socket = @stream_socket_client($this->remoteSocketAddress, $errCode, $errStr, self::SOCKET_TIMEOUT);

        if (!$socket) {
            throw new WebSocket\ConnectionError(
                sprintf('WebSocket connection error %s (%u): %s', $this->remoteSocketAddress, $errCode, $errStr)
            );
        }

        if (false === stream_set_timeout($socket, self::SOCKET_TIMEOUT)) {
            throw new WebSocket\SetupFailure(
                'WebSocket set timeout failure. Can not set timeout for stream.'
            );
        }

        return $socket;
    }

    /**
     * @param string $header
     */
    protected function handshake($header)
    {
        if (!fwrite($this->socket, $header)) {
            throw new WebSocket\SocketWriteError(
                sprintf('WebSocket write error. (remote_socket: %s)', $this->remoteSocketAddress)
            );
        }

        $serverHandshake = fread($this->socket, 32);
        if (!$serverHandshake) {
            throw new WebSocket\SocketReadError(
                sprintf('WebSocket read error. (remote_socket: %s)', $this->remoteSocketAddress)
            );
        }

        if (strpos($serverHandshake, static::SERVER_HANDSHAKE_STATUS_LINE) !== 0) {
            throw new WebSocket\HandshakeFailure(sprintf(
                'Websocket server handshake expect status line to be "%s", but "%s" was returned (remote_socket: %s)',
                static::SERVER_HANDSHAKE_STATUS_LINE,
                $serverHandshake,
                $this->remoteSocketAddress
            ));
        }
    }
}

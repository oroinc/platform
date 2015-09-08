<?php

namespace Oro\Bundle\SyncBundle\WebSocket\Client;

class Rfc6455
{
    /**
     * Default socket connection timeout, in seconds
     */
    const SOCKET_TIMEOUT = 2;

    /** @var resource */
    protected $socket;

    /**
     *
     * @param string $host
     * @param int $port
     *
     * @return resource
     */
    public function connect($host, $port)
    {
        if ($this->socket) {
            return $this->socket;
        }

        $websocketKey = base64_encode($this->generateRandomString(16));

        $header  = "GET /echo HTTP/1.1\r\n";
        $header .= 'Host: ' . $host . ':' . $port . "\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Connection: Upgrade\r\n";
        $header .= "Sec-WebSocket-Key: " . $websocketKey . "\r\n";
        $header .= "Sec-WebSocket-Version: 13\r\n";
        $header .= "\r\n";

        $this->socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, self::SOCKET_TIMEOUT);

        if (!$this->socket) {
            throw new \RuntimeException(sprintf('WebSocket connection error (%u): %s', $errno, $errstr));
        }

        if (!fwrite($this->socket, $header)) {
            throw new \RuntimeException('WebSocket write error');
        }

        stream_set_blocking($this->socket, false);

        return $this->socket;
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
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @param   bool    $mask       Whether the message will be masked or not.
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

        $maskingKey = [];
        if (function_exists('openssl_random_pseudo_bytes')) {
            $maskingKey = array_map(
                'ord',
                str_split(
                    openssl_random_pseudo_bytes(4)
                )
            );
        } else {
            for ($i = 0; $i < 4; ++$i) {
                $maskingKey[] = mt_rand(1, 255);
            }
        }

        for ($i = 0, $max = strlen($message); $i < $max; ++$i) {
            $message[$i] = chr(ord($message[$i]) ^ $maskingKey[$i % 4]);
        }

        return implode('', array_map('chr', $maskingKey)) . $message;
    }

    /**
     * Generate random string for a WebSocket handshake request headers
     *
     * @param  int    $length
     * @param  bool   $addSpaces
     * @param  bool   $addNumbers
     * @return string
     */
    protected function generateRandomString($length = 10, $addSpaces = true, $addNumbers = true)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $useChars   = array();

        // select some random chars:
        for ($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters)-1)];
        }

        // add spaces and numbers:
        if ($addSpaces === true) {
            array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
        }

        if ($addNumbers === true) {
            array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        }

        shuffle($useChars);

        $randomString = trim(implode('', $useChars));

        return substr($randomString, 0, $length);
    }
}

<?php

/**
 * Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 * disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other materials provided with the distribution.
 * - Neither the name of Laminas Foundation nor the names of its contributors may be used to endorse or promote
 * products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This file is a copy of {@see Laminas\Mail\Header\Sender}
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\Header\Exception;
use Laminas\Mail\Header\Sender as BaseSender;

/**
 * Sender header that uses overridden GenericHeader during value parsing.
 */
class Sender extends BaseSender
{
    /**
     * {@inheritdoc}
     *
     * This method is a copy of {@see \Laminas\Mail\Header\Sender::fromString}.
     * It is needed to override static call of `GenericHeader::splitHeaderLine`.
     */
    public static function fromString($headerLine)
    {
        [$name, $value] = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'sender') {
            throw new Exception\InvalidArgumentException('Invalid header name for Sender string');
        }

        $header = new static();

        /**
         * matches the header value so that the email must be enclosed by < > when a name is present
         * 'name' and 'email' capture groups correspond respectively to 'display-name' and 'addr-spec' in the ABNF
         * @see https://tools.ietf.org/html/rfc5322#section-3.4
         */
        $hasMatches = preg_match(
            '/^(?:(?P<name>.+)|((name>.+)\s))?(?(name)<|<?)(?P<email>[^\s]+?)(?(name)>|>?)$/',
            $value,
            $matches
        );

        $senderName = '';
        $address = '';
        if ($hasMatches === 1) {
            $senderName = trim($matches['name']);
            $address = $matches['email'];
        }

        if (empty($senderName)) {
            $senderName = null;
        }

        $header->setAddress($address, $senderName);

        return $header;
    }
}

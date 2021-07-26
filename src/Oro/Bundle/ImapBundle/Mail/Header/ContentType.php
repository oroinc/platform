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
 * This file is a copy of {@see Laminas\Mail\Header\ContentType}
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\Header\ContentType as BaseContentType;
use Laminas\Mail\Header\Exception\InvalidArgumentException;
use Oro\Bundle\ImapBundle\Mail\Headers;

/**
 * Content type header that allows to sync emails with non-standard value of the header.
 */
class ContentType extends BaseContentType
{
    /** @var Headers */
    protected static $headers;

    /**
     * {@inheritdoc}
     */
    public static function fromString($headerLine)
    {
        $headerLine = iconv_mime_decode($headerLine, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        [$name, $value] = explode(': ', $headerLine, 2);

        // check to ensure proper header type for this factory
        if (self::getHeaders()->normalizeFieldName($name) !== 'contenttype') {
            throw new InvalidArgumentException('Invalid header line for Content-Type string');
        }

        $value = str_replace(Headers::FOLDING, " ", $value);
        $values = preg_split('#\s*;\s*#', $value);
        $type = array_shift($values);

        $header = new static();
        $header->setType($type);

        if (count($values)) {
            foreach ($values as $keyValuePair) {
                if ($keyValuePair && str_contains($keyValuePair, '=')) {
                    [$key, $value] = explode('=', $keyValuePair, 2);
                    $value = trim($value, "'\" \t\n\r\0\x0B");
                    $header->addParameter($key, $value);
                }
            }
        }

        return $header;
    }

    /**
     * @return Headers
     */
    protected static function getHeaders()
    {
        if (self::$headers === null) {
            self::$headers = new Headers();
        }

        return self::$headers;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $matches = [];
        if (preg_match('/^[a-z\-]+\/[a-z0-9\.\+\-]+/i', $type, $matches)) {
            $type = $matches[0];
        }
        $this->type = $type;
        return $this;
    }
}

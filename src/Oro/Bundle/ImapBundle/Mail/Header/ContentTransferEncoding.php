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
 * This file is a copy of {@see Laminas\Mail\Header\ContentTransferEncoding}
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\Header\ContentTransferEncoding as BaseContentTransferEncoding;
use Laminas\Mail\Header\Exception\InvalidArgumentException;

/**
 * Content transfer header that adds support of additional encoding types
 * and allows to process the header value with extra data.
 */
class ContentTransferEncoding extends BaseContentTransferEncoding
{
    /**
     * {@inheritdoc}
     */
    protected static $allowedTransferEncodings = [
        '7bit',
        '8bit',
        'quoted-printable',
        'base64',
        'binary',
        'utf-8',
        'windows-1251'
        /*
         * not implemented:
         * x-token: 'X-'
         */
    ];

    /**
     * {@inheritdoc}
     */
    public function setTransferEncoding($transferEncoding)
    {
        // Per RFC 1521, the value of the header is not case sensitive
        $transferEncoding = strtolower($transferEncoding);

        // support of '7bit boundary="_av-21755293853469785109"' and '7bi tboundary' header values
        $transferEncodingString = str_replace(' ', '', $transferEncoding);
        foreach (static::$allowedTransferEncodings as $encoding) {
            if (str_starts_with($transferEncodingString, $encoding)) {
                $this->transferEncoding = $encoding;

                return $this;
            }
        }

        throw new InvalidArgumentException(sprintf(
            '%s expects one of "'. implode(', ', static::$allowedTransferEncodings) . '"; received "%s"',
            __METHOD__,
            (string) $transferEncoding
        ));
    }
}

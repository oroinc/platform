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
 * This file is a copy of {@see Laminas\Mail\Header\HeaderWrap}
 */
namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\Header\HeaderWrap as BaseHeaderWrap;
use Oro\Bundle\ImapBundle\Mail\Headers;

/**
 * Utility class that can be used to decode header values.
 * Allows to decode the header value in case if iconv_mime_decode cannot decode the header value.
 */
abstract class HeaderWrap extends BaseHeaderWrap
{
    /**
     * {@inheritdoc}
     */
    public static function mimeDecodeValue($value)
    {
        // unfold first, because iconv_mime_decode is discarding "\n" with no apparent reason
        // making the resulting value no longer valid.

        // see https://tools.ietf.org/html/rfc2822#section-2.2.3 about unfolding
        $parts = explode(Headers::FOLDING, $value);
        $value = implode(' ', $parts);

        $decodedValue = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        // imap (unlike iconv) can handle multibyte headers which are splitted across multiple line
        if ((false === $decodedValue || self::isNotDecoded($value, $decodedValue)) && extension_loaded('imap')) {
            $decodedValue = array_reduce(
                imap_mime_header_decode(imap_utf8($value)),
                function ($accumulator, $headerPart) {
                    return $accumulator . $headerPart->text;
                },
                ''
            );
        }

        // clear broken non UTF-8 chars
        $result = @iconv("UTF-8", "UTF-8//IGNORE", $decodedValue);

        return $result ? $result : '';
    }

    /**
     * {@inheritdoc}
     */
    private static function isNotDecoded($originalValue, $value)
    {
        $startBlockPosition = strpos($value, '=?');
        $endBlockPosition = strpos($value, '?=');

        return $startBlockPosition !== false
            && $endBlockPosition !== false
            && $startBlockPosition < $endBlockPosition;
    }
}

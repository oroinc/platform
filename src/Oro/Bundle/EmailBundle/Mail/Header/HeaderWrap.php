<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\HeaderWrap}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\EmailBundle\Mail\Header;

use Oro\Bundle\EmailBundle\Mail\Headers;
use Zend\Mail\Header\HeaderWrap as BaseHeaderWrap;

/**
 * Utility class used for creating wrapped or MIME-encoded versions of header values.
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

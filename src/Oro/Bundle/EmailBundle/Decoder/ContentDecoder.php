<?php

namespace Oro\Bundle\EmailBundle\Decoder;

use Oro\Component\PhpUtils\Encoding\CharsetAlias;
use Oro\Component\PhpUtils\Encoding\Windows1250;

/**
 * Decode the given string
 */
class ContentDecoder
{
    /**
     * Decode the given string
     *
     * @param string $str The string being encoded.
     * @param string|null $contentTransferEncoding The type of Content-Transfer-Encoding that $str is encoded.
     * @param string|null $fromEncoding The type of encoding that $str is encoded.
     * @param string|null $toEncoding The type of encoding that $str is being converted to.
     * @return string
     */
    public static function decode($str, $contentTransferEncoding = null, $fromEncoding = null, $toEncoding = null)
    {
        $str = self::decodeTransferEncoding($str, $contentTransferEncoding);

        return self::convertEncoding($str, $fromEncoding, $toEncoding);
    }

    private static function decodeTransferEncoding($str, $contentTransferEncoding)
    {
        return match (strtolower((string)$contentTransferEncoding)) {
            'base64' => base64_decode($str),
            'quoted-printable' => quoted_printable_decode($str),
            default => $str,
        };
    }

    private static function convertEncoding($str, $fromEncoding, $toEncoding)
    {
        if (empty($fromEncoding) || empty($toEncoding) || strtolower($fromEncoding) === strtolower($toEncoding)) {
            return $str;
        }

        $fromEncoding = CharsetAlias::resolve($fromEncoding);
        $toEncoding = CharsetAlias::resolve($toEncoding);

        // work around for php-8.1.6-1.el8.remi iconv library version => 2.28
        // with iconv library version => 2.35 should be reverted
        if (Windows1250::isSupported($toEncoding, $fromEncoding)) {
            return Windows1250::convert($str, $toEncoding, $fromEncoding);
        }

        try {
            // Added additional option to avoid `illegal character` iconv decoding error
            $converted = @iconv($fromEncoding, $toEncoding.'//TRANSLIT//IGNORE', $str)
                ?: mb_convert_encoding($str, $toEncoding, $fromEncoding);
        } catch (\ValueError $e) {
            $converted = false;
        }

        return false === $converted ? $str : $converted;
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Decoder;

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
        if (!empty($contentTransferEncoding)) {
            switch (strtolower($contentTransferEncoding)) {
                case 'base64':
                    $str = base64_decode($str);
                    break;
                case 'quoted-printable':
                    $str = quoted_printable_decode($str);
                    break;
            }
        }
        if (!empty($fromEncoding) && !empty($toEncoding) && strtolower($fromEncoding) !== strtolower($toEncoding)) {
            // Added additional option to avoid `illegal character` iconv decoding error
            $toEncodingIconv = $toEncoding.'//TRANSLIT//IGNORE';
            // work around for php-8.1.6-1.el8.remi iconv library version => 2.28
            // with iconv library version => 2.35 should be reverted
            if (Windows1250::isSupported($toEncoding, $fromEncoding)) {
                $str = Windows1250::convert($str, $toEncoding, $fromEncoding);
            } else {
                $str = @iconv($fromEncoding, $toEncodingIconv, $str) ?:
                    mb_convert_encoding($str, $toEncoding, $fromEncoding);
            }
        }

        return $str;
    }
}

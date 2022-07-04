<?php

namespace Oro\Component\PhpUtils\Encoding;

/**
 * Windows-1250 encoding utils
 */
class Windows1250
{
    private const NAME = 'windows-1250';
    private const UTF8 = 'utf-8';

    private const CHARSET_UTF8 = [
        "c1" => "c381", // Á
        "c9" => "c389", // É
        "cc" => "c49a", // Ě
        "cd" => "c38d", // Í
        "dd" => "c39d", // Ý
        "d3" => "c393", // Ó
        "da" => "c39a", // Ú
        "d9" => "c5ae", // Ů
        "c4" => "c384", // Ä
        "c5" => "c4b9", // Ĺ
        "bc" => "c4bd", // Ľ
        "d4" => "c394", // Ô
        "8e" => "c5bd", // Ž
        "8a" => "c5a0", // Š
        "c8" => "c48c", // Č
        "d8" => "c598", // Ř
        "cf" => "c48e", // Ď
        "8d" => "c5a4", // Ť
        "d2" => "c587", // Ň
        "e1" => "c3a1", // á
        "e9" => "c3a9", // é
        "ec" => "c49b", // ě
        "ed" => "c3ad", // í
        "fd" => "c3bd", // ý
        "f3" => "c3b3", // ó
        "fa" => "c3ba", // ú
        "f9" => "c5af", // ů
        "e4" => "c3a4", // ä
        "e5" => "c4ba", // ĺ
        "be" => "c4be", // ľ
        "f4" => "c3b4", // ô
        "9e" => "c5be", // ž
        "9a" => "c5a1", // š
        "e8" => "c48d", // č
        "f8" => "c599", // ř
        "ef" => "c48f", // ď
        "9d" => "c5a5", // ť
        "f2" => "c588", // ň
        "f6" => "c3b6", // ö
        "f5" => "c591", // ő
        "fc" => "c3bc", // ü
        "d6" => "c396", // Ö
        "d5" => "c590", // Ő
        "dc" => "c39c", // Ü
        "b0" => "c2b0",
        'df' => "c39f",
        "e0" => "c595", // ŕ
        "c0" => "c594", // Ŕ
        "b4" => "c2b4" // ´
    ];

    public static function isSupported(string $toEncoding, string $fromEncoding): bool
    {
        $encodings = [self::NAME, self::UTF8];

        return \in_array(strtolower($toEncoding), $encodings, true)
            && \in_array(strtolower($fromEncoding), $encodings, true)
            && !\in_array('Windows-1250', mb_list_encodings(), true);
    }

    public static function convert(string $input, string $toEncoding, string $fromEncoding): string
    {
        $toEncodingTok = strtolower($toEncoding);
        $fromEncodingTok = strtolower($fromEncoding);

        if (self::NAME === $fromEncodingTok && self::UTF8 === $toEncodingTok) {
            return self::toUtf8($input);
        }

        if (self::UTF8 === $fromEncodingTok && self::NAME === $toEncodingTok) {
            return self::fromUtf8($input);
        }

        return $input;
    }

    /**
     * @param string $input which will be converted
     * @return string
     */
    public static function fromUtf8(string $input): string
    {
        $charset = array_flip(self::CHARSET_UTF8);
        $ret = '';
        $len = mb_strlen($input, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            $char = bin2hex(mb_substr($input, $i, 1, "UTF-8"));
            $ret .= $charset[$char] ?? $char;
        }

        return hex2bin($ret);
    }

    /**
     * @param string $input which will be converted
     * @return string
     */
    public static function toUtf8(string $input): string
    {
        $charset = self::CHARSET_UTF8;
        $ret = '';
        $len = strlen($input);

        for ($i = 0; $i < $len; $i++) {
            $char = bin2hex($input[$i]);
            $ret .= $charset[$char] ?? $char;
        }

        return hex2bin($ret);
    }
}

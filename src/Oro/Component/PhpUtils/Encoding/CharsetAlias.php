<?php

namespace Oro\Component\PhpUtils\Encoding;

/**
 * Maps charset labels that neither iconv nor mbstring recognizes to canonical encoding names they support.
 */
class CharsetAlias
{
    private const ALIASES = [
        'chinese' => 'GBK',
        'csbig5' => 'BIG-5',
        'csiso58gb231280' => 'GBK',
        'csiso88596e' => 'ISO-8859-6',
        'csiso88596i' => 'ISO-8859-6',
        'csiso88598e' => 'ISO-8859-8',
        'csisolatin9' => 'ISO-8859-15',
        'csksc56011987' => 'CP949',
        'dos-874' => 'windows-874',
        'gb_2312' => 'GBK',
        'gb_2312-80' => 'GBK',
        'iso-8859-6-e' => 'ISO-8859-6',
        'iso-8859-6-i' => 'ISO-8859-6',
        'iso-8859-8-e' => 'ISO-8859-8',
        'iso-8859-8-i' => 'ISO-8859-8',
        'iso-ir-149' => 'CP949',
        'iso-ir-58' => 'GBK',
        'koi' => 'KOI8-R',
        'koi8_r' => 'KOI8-R',
        'korean' => 'CP949',
        'ks_c_5601-1987' => 'CP949',
        'ks_c_5601-1989' => 'CP949',
        'ksc5601' => 'CP949',
        'ksc_5601' => 'CP949',
        'l9' => 'ISO-8859-15',
        'logical' => 'ISO-8859-8',
        'sun_eu_greek' => 'ISO-8859-7',
        'unicode-1-1-utf-8' => 'UTF-8',
        'unicode11utf8' => 'UTF-8',
        'unicode20utf8' => 'UTF-8',
        'unicodefeff' => 'UTF-16LE',
        'unicodefffe' => 'UTF-16BE',
        'visual' => 'ISO-8859-8',
        'windows-949' => 'CP949',
        'x-cp1250' => 'windows-1250',
        'x-cp1251' => 'windows-1251',
        'x-cp1252' => 'windows-1252',
        'x-cp1253' => 'windows-1253',
        'x-cp1254' => 'windows-1254',
        'x-cp1255' => 'windows-1255',
        'x-cp1256' => 'windows-1256',
        'x-cp1257' => 'windows-1257',
        'x-cp1258' => 'windows-1258',
        'x-gbk' => 'GBK',
        'x-mac-roman' => 'macintosh',
        'x-unicode20utf8' => 'UTF-8',
        'x-x-big5' => 'BIG-5',
    ];

    public static function resolve(string $encoding): string
    {
        return self::ALIASES[strtolower($encoding)] ?? $encoding;
    }
}

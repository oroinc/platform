<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

/**
 * Removes non printable symbols form string.
 */
class NonPrintableCharsStringSanitizer
{
    /**
     * @param string $string
     *
     * @return string|null
     */
    public function removeNonPrintableCharacters($string)
    {
        /*
         * [:print:] character class couldn't be used in \u mode on old versions of pcre
         *
         * @link https://www.pcre.org/original/changelog.txt
         *
         * Version 8.34 15-December-2013
         * 31. Upgraded the handling of the POSIX classes [:graph:], [:print:],
         * and [:punct:] when PCRE_UCP is set so as to include the same characters as Perl does in Unicode mode.
         *
         * Before this version [:print:] didn't contain Unicode characters.
         */

        /*
         * @link https://www.pcre.org/current/doc/html/pcre2pattern.html
         *
         * [:print:] This matches the same characters as [:graph:] plus space characters that are not controls,
         * that is, characters with the Zs property.
         *
         * [:graph:] This matches characters that have glyphs that mark the page when printed.
         * In Unicode property terms, it matches all characters with the L, M, N, P, S, or Cf properties.
         *
         * Also should be available "\n" characters that is Line feed for multi-line text (LF has code U+000A).
         */
        $printProperties = '\p{L}\p{M}\p{N}\p{P}\p{S}\p{Cf}\p{Z}\x{000A}';

        /*
         * except for:
         *
         * U+061C           Arabic Letter Mark
         * U+180E           Mongolian Vowel Separator
         * U+2066 - U+2069  Various "isolate"s
         */
        $exclusions = '\x{061C}\x{180E}\x{2066}\x{2067}\x{2068}\x{2069}';

        $pattern = sprintf('/([^%s]|[%s])/u', $printProperties, $exclusions);

        return preg_replace($pattern, '', $string);
    }
}

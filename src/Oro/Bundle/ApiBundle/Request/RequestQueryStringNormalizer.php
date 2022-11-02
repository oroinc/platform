<?php

/**
 * This file is partly copied from the class Symfony\Component\HttpFoundation\Request authored
 * by (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Symfony 4 changed Request::normalizeQueryString() - https://github.com/symfony/symfony/pull/26220
 * Due to our logic has own filters (query params) which is not compatible with new implementation
 * we use old approach till we change our logic to use new syntax.
 */
class RequestQueryStringNormalizer
{
    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     *
     * @return string A normalized query string for the Request
     */
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs) {
            return '';
        }

        $parts = [];
        $order = [];

        foreach (explode('&', $qs) as $param) {
            if ('' === $param || '=' === $param[0]) {
                // Ignore useless delimiters, e.g. "x=y&".
                // Also ignore pairs with empty key, even if there was a value, e.g. "=value",
                // as such nameless values cannot be retrieved anyway.
                // PHP also does not include them when building _GET.
                continue;
            }

            $keyValuePair = explode('=', $param, 2);

            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default
            // (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function
            // parse_str. This is why we use urldecode and then normalize to
            // RFC 3986 with rawurlencode.
            $parts[] = isset($keyValuePair[1]) ?
                rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1])) :
                rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }

        array_multisort($order, SORT_ASC, $parts);

        return implode('&', $parts);
    }
}

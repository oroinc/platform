<?php

/**
 * This file is partly copied from the class Symfony\Component\HttpFoundation\Tests\RequestTest authored
 * by (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RequestQueryStringNormalizer;

class RequestQueryStringNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeQueryStringDataProvider
     */
    public function testNormalizeQueryString(string $query, string $expectedQuery, string $msg): void
    {
        self::assertSame($expectedQuery, RequestQueryStringNormalizer::normalizeQueryString($query), $msg);
    }

    public function normalizeQueryStringDataProvider(): array
    {
        return [
            ['foo', 'foo', 'works with valueless parameters'],
            ['foo=', 'foo=', 'includes a dangling equal sign'],
            ['bar=&foo=bar', 'bar=&foo=bar', '->works with empty parameters'],
            ['foo=bar&bar=', 'bar=&foo=bar', 'sorts keys alphabetically'],

            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in
            // enctype application/x-www-form-urlencoded). PHP also converts "+" to spaces when filling the global
            // _GET or when using the function parse_str.
            [
                'him=John%20Doe&her=Jane+Doe',
                'her=Jane%20Doe&him=John%20Doe',
                'normalizes spaces in both encodings "%20" and "+"'
            ],

            ['foo[]=1&foo[]=2', 'foo%5B%5D=1&foo%5B%5D=2', 'allows array notation'],
            ['foo=1&foo=2', 'foo=1&foo=2', 'allows repeated parameters'],
            [
                'pa%3Dram=foo%26bar%3Dbaz&test=test',
                'pa%3Dram=foo%26bar%3Dbaz&test=test',
                'works with encoded delimiters'
            ],
            ['0', '0', 'allows "0"'],
            ['Jane Doe&John%20Doe', 'Jane%20Doe&John%20Doe', 'normalizes encoding in keys'],
            ['her=Jane Doe&him=John%20Doe', 'her=Jane%20Doe&him=John%20Doe', 'normalizes encoding in values'],
            ['foo=bar&&&test&&', 'foo=bar&test', 'removes unneeded delimiters'],
            [
                'formula=e=m*c^2',
                'formula=e%3Dm%2Ac%5E2',
                'correctly treats only the first "=" as delimiter and the next as value'
            ],

            // Ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be
            // retrieved anyway. PHP also does not include them when building _GET.
            ['foo=bar&=a=b&=x=y', 'foo=bar', 'removes params with empty key'],

            ['', '', 'returns empty string for empty query string'],
        ];
    }
}

<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Validator;

use Oro\Bundle\ConfigBundle\Validator\OutboundConnectionValidator;
use PHPUnit\Framework\TestCase;

class OutboundConnectionValidatorTest extends TestCase
{
    /**
     * @dataProvider isConnectionAllowedWhenNoRulesDataProvider
     */
    public function testIsConnectionAllowedWhenNoRules(?string $rules): void
    {
        $connectionValidator = new OutboundConnectionValidator($rules);
        self::assertTrue($connectionValidator->isConnectionAllowed('any-host.com', 80));
    }

    public static function isConnectionAllowedWhenNoRulesDataProvider(): array
    {
        return [
            [null],
            [''],
            [' ']
        ];
    }

    /**
     * @dataProvider isConnectionAllowedDataProvider
     */
    public function testIsConnectionAllowed(bool $isMatched, string $host, int $port): void
    {
        $connectionValidator = new OutboundConnectionValidator(
            'Host1.Com;'
            . ' host2.com:443 ;'
            . ' *.host3.com ;'
            . '*.Host4.Com:443;'
            . ' *.host5.com : 443, 8000-8100, 8800 ;'
            . ' ESPAÑOL.ES : 8080 ' // URL with multi-byte character
        );
        self::assertSame($isMatched, $connectionValidator->isConnectionAllowed($host, $port));
    }

    public static function isConnectionAllowedDataProvider(): array
    {
        return [
            [true, 'Host1.Com', 80],
            [true, 'host1.com', 80],
            [true, 'HOST1.COM', 80],
            [true, 'host1.com?p1=1&p2=1', 80],
            [true, 'host1.com/?p1=1&p2=1', 80],
            [true, 'host1.com/resource?p1=1&p2=1', 80],
            [true, 'host1.com/resource/?p1=1&p2=1', 80],
            [true, 'host1.com#fragment', 80],
            [true, 'host1.com/#fragment', 80],
            [true, 'host1.com/resource#fragment', 80],
            [true, 'host1.com/resource/#fragment', 80],
            [true, 'host2.com', 443],
            [true, 'HOST2.COM', 443],
            [false, 'host2.com', 80],
            [false, 'HOST2.COM', 80],
            [true, 'my.host3.com', 80],
            [true, 'my.other.host3.com', 80],
            [false, '.host3.com', 80],
            [false, 'host3.com', 80],
            [true, 'my.Host4.Com', 443],
            [true, 'my.host4.com', 443],
            [true, 'MY.HOST4.COM', 443],
            [false, 'my.host4.com', 80],
            [false, '.host4.com', 443],
            [false, '.host4.com', 80],
            [false, 'host4.com', 443],
            [false, 'host4.com', 80],
            [true, 'my.host5.com', 443],
            [true, 'my.host5.com', 8000],
            [true, 'my.host5.com', 8050],
            [true, 'my.host5.com', 8100],
            [true, 'my.host5.com', 8800],
            [false, 'my.host5.com', 80],
            [false, '.host5.com', 443],
            [false, '.host5.com', 80],
            [false, 'host5.com', 443],
            [false, 'host5.com', 80],
            // test URL with multi-byte character
            [true, 'Español.Es', 8080],
            [true, 'español.es', 8080],
            [true, 'ESPAÑOL.ES', 8080],
            [true, 'español.es?p1=1&p2=1', 8080],
            [true, 'español.es/?p1=1&p2=1', 8080],
            [true, 'español.es/resource?p1=1&p2=1', 8080],
            [true, 'español.es/resource/?p1=1&p2=1', 8080],
            [true, 'español.es#fragment', 8080],
            [true, 'español.es/#fragment', 8080],
            [true, 'español.es/resource#fragment', 8080],
            [true, 'español.es/resource/#fragment', 8080],
            [false, 'español.es', 80],
            [false, 'espanol.es', 8080]
        ];
    }
}

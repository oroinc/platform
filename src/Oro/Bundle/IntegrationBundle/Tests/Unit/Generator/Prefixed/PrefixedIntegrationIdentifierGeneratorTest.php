<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Generator\Prefixed;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;

class PrefixedIntegrationIdentifierGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateIdentifier(): void
    {
        $prefix = 'somePrefix';
        $channelId = 5;

        $channel = $this->createMock(Channel::class);
        $channel->expects(self::once())
            ->method('getId')
            ->willReturn($channelId);

        $generator = new PrefixedIntegrationIdentifierGenerator($prefix);
        $actualResult = $generator->generateIdentifier($channel);

        self::assertEquals($prefix . '_' . $channelId, $actualResult);
    }

    public function testParseIdentifierReturnsCorrectPrefixAndId(): void
    {
        $identifier = 'money_order_42';

        [$prefix, $id] = PrefixedIntegrationIdentifierGenerator::parseIdentifier($identifier);

        self::assertEquals('money_order', $prefix);
        self::assertSame(42, $id);
    }

    public function testParseIdentifierReturnsFullIdentifierAsPrefixWhenNoId(): void
    {
        $identifier = 'money_order';

        [$prefix, $id] = PrefixedIntegrationIdentifierGenerator::parseIdentifier($identifier);

        self::assertEquals('money_order', $prefix);
        self::assertNull($id);
    }

    public function testParseIdentifierReturnsEmptyPrefixWhenIdentifierIsEmpty(): void
    {
        $identifier = '';

        [$prefix, $id] = PrefixedIntegrationIdentifierGenerator::parseIdentifier($identifier);

        self::assertEquals('', $prefix);
        self::assertEquals('', $id);
    }

    public function testParseIdentifierHandlesNonNumeric(): void
    {
        $identifier = 'money_order_42_extra';

        [$prefix, $id] = PrefixedIntegrationIdentifierGenerator::parseIdentifier($identifier);

        self::assertEquals('money_order_42_extra', $prefix);
        self::assertNull($id);
    }
}

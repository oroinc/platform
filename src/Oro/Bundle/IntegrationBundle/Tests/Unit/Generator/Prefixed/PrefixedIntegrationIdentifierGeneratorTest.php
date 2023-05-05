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
}

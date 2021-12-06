<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Generator\Prefixed;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;

class PrefixedIntegrationIdentifierGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateIdentifier()
    {
        $prefix = 'somePrefix';
        $channelId = 5;

        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getId')
            ->willReturn($channelId);

        $expectedResult = sprintf('%s_%s', $prefix, $channelId);

        $generator = new PrefixedIntegrationIdentifierGenerator($prefix);
        $actualResult = $generator->generateIdentifier($channel);

        $this->assertEquals($expectedResult, $actualResult);
    }
}

<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Generator\Prefixed;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;

class PrefixedIntegrationIdentifierGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Channel|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createChannelMock()
    {
        return $this->createMock(Channel::class);
    }

    public function testGenerateIdentifier()
    {
        $prefix = 'somePrefix';
        $channelId = 5;

        $channelMock = $this->createChannelMock();

        $channelMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($channelId);

        $expectedResult = sprintf('%s_%s', $prefix, $channelId);

        $generator = new PrefixedIntegrationIdentifierGenerator($prefix);
        $actualResult = $generator->generateIdentifier($channelMock);

        $this->assertEquals($expectedResult, $actualResult);
    }
}

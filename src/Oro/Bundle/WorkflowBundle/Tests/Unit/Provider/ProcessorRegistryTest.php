<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;

class ProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SyncProcessorRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new SyncProcessorRegistry();
    }

    public function testRegistry()
    {
        $channelOne = $this->createMock(Channel::class);
        $channelOne->expects($this->any())
            ->method('getType')
            ->willReturn('test1');

        $channelTwo = $this->createMock(Channel::class);
        $channelTwo->expects($this->any())
            ->method('getType')
            ->willReturn('test2');

        $customProcessor = $this->getMockBuilder(AbstractSyncProcessor::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $defaultProcessor = $this->getMockBuilder(AbstractSyncProcessor::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registry->setDefaultProcessor($defaultProcessor);
        $this->registry->addProcessor('test1', $customProcessor);

        $this->assertEquals($defaultProcessor, $this->registry->getDefaultProcessor());
        $this->assertTrue($this->registry->hasProcessorForIntegration($channelOne));
        $this->assertFalse($this->registry->hasProcessorForIntegration($channelTwo));
        $this->assertEquals($customProcessor, $this->registry->getProcessorForIntegration($channelOne));
        $this->assertEquals($defaultProcessor, $this->registry->getProcessorForIntegration($channelTwo));
    }

    public function testRegistryException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Default sync processor was not set');

        $channelOne = $this->createMock(Channel::class);
        $channelOne->expects($this->any())
            ->method('getType')
            ->willReturn('test1');

        $this->registry->getProcessorForIntegration($channelOne);
    }
}

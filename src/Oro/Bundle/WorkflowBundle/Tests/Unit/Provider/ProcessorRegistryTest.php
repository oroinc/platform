<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;

class ProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SyncProcessorRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new SyncProcessorRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testRegistry()
    {
        $channelOne = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();
        $channelOne->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('test1'));

        $channelTwo = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();
        $channelTwo->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('test2'));

        $customProcessor = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $defaultProcessor = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor')
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

    /**
     * @expectedException \Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Default sync processor was not set
     */
    public function testRegistryException()
    {
        $channelOne = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();
        $channelOne->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('test1'));

        $this->registry->getProcessorForIntegration($channelOne);
    }
}

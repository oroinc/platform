<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBag;

class ActionProcessorBagTest extends \PHPUnit_Framework_TestCase
{
    public function testGetProcessor()
    {
        $processor = $this->getMockBuilder('Oro\Component\ChainProcessor\ActionProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects($this->once())
            ->method('getAction')
            ->willReturn('test');

        $actionProcessorBag = new ActionProcessorBag();
        $actionProcessorBag->addProcessor($processor);

        $this->assertSame($processor, $actionProcessorBag->getProcessor('test'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A processor for "unknown" action was not found.
     */
    public function testGetUnknownProcessor()
    {
        $processor = $this->getMockBuilder('Oro\Component\ChainProcessor\ActionProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects($this->once())
            ->method('getAction')
            ->willReturn('test');

        $actionProcessorBag = new ActionProcessorBag();
        $actionProcessorBag->addProcessor($processor);

        $actionProcessorBag->getProcessor('unknown');
    }
}

<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBag;

class ChainProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testExecuteProcessors()
    {
        $processor1 = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');
        $processor2 = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');

        $factory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory->expects($this->exactly(2))
            ->method('getProcessor')
            ->willReturnOnConsecutiveCalls($processor1, $processor2);

        $processorBag = new ProcessorBag($factory);
        $processorBag->addProcessor('processor1', [], 'action1', null, 20);
        $processorBag->addProcessor('processor2', [], 'action1', null, 10);

        $context = new Context();
        $context->setAction('action1');

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor2->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $chainProcessor = new ChainProcessorMock($processorBag);
        $chainProcessor->process($context);
    }
}

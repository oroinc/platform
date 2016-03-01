<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainProcessor;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
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

        $chainProcessor = new ChainProcessor($processorBag);
        $chainProcessor->process($context);
    }

    public function testExecuteProcessorsFailure()
    {
        $processor1 = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');
        $processor2 = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');

        $factory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory->expects($this->exactly(2))
            ->method('getProcessor')
            ->willReturnOnConsecutiveCalls($processor1, $processor2);

        $processorBag = new ProcessorBag($factory);
        $processorBag->addGroup('group1', 'action1', 20);
        $processorBag->addGroup('group2', 'action1', 10);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1', 20);
        $processorBag->addProcessor('processor2', [], 'action1', 'group2', 20);
        $processorBag->addProcessor('processor3', [], 'action1', 'group2', 10);

        $context = new Context();
        $context->setAction('action1');

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor2->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException(new \Exception('Some error.'));

        $chainProcessor = new ChainProcessor($processorBag);

        try {
            $chainProcessor->process($context);
            $this->fail('An exception expected');
        } catch (ExecutionFailedException $e) {
            $this->assertEquals('Processor failed: "processor2". Reason: Some error.', $e->getMessage());
            $this->assertEquals('processor2', $e->getProcessorId());
            $this->assertEquals('action1', $e->getAction());
            $this->assertEquals('group2', $e->getGroup());
            $this->assertNotNull($e->getPrevious());
            $this->assertEquals('Some error.', $e->getPrevious()->getMessage());
        } catch (\Exception $e) {
            $this->fail(sprintf('ExecutionFailedException expected. Got: %s', get_class($e)));
        }
    }
}

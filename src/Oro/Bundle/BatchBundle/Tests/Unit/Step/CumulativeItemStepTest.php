<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\ExitStatus;
use Akeneo\Bundle\BatchBundle\Tests\Unit\Step\Stub\ProcessorStub;
use Akeneo\Bundle\BatchBundle\Tests\Unit\Step\Stub\ReaderStub;
use Oro\Bundle\BatchBundle\Step\CumulativeItemStep;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\ClosableWriterStub;

class CumulativeItemStepTest extends ItemStepTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->itemStep = new CumulativeItemStep(self::STEP_NAME);

        $this->itemStep->setEventDispatcher($this->eventDispatcher);
        $this->itemStep->setJobRepository($this->jobRepository);
    }

    public function testExecute()
    {
        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->willReturn(new ExitStatus());

        $reader = $this->getMockBuilder(ReaderStub::class)
            ->setMethods(['setStepExecution', 'read'])
            ->getMock();
        $reader->expects($this->once())->method('setStepExecution')->with($stepExecution);
        $reader->expects($this->exactly(8))
            ->method('read')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7, null));

        $processor = $this->getMockBuilder(ProcessorStub::class)
            ->setMethods(['setStepExecution', 'process'])
            ->getMock();
        $processor->expects($this->once())->method('setStepExecution')->with($stepExecution);
        $processor->expects($this->exactly(7))
            ->method('process')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7));

        $writer = $this->getMockBuilder(ClosableWriterStub::class)
            ->setMethods(['setStepExecution', 'write', 'close'])
            ->getMock();
        $writer->expects($this->once())->method('setStepExecution')->with($stepExecution);
        $writer->expects($this->exactly(7))->method('write');
        $writer->expects($this->once())->method('close');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);
        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }
}

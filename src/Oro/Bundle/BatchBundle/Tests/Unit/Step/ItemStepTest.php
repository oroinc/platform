<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;
use Oro\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\ProcessorStub;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\ReaderStub;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\WriterStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ItemStepTest extends \PHPUnit\Framework\TestCase
{
    private const STEP_NAME = 'test_step_name';

    private ItemStep $itemStep;

    protected function setUp(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $jobRepository = $this->createMock(JobRepositoryInterface::class);

        $this->itemStep = new ItemStep(self::STEP_NAME);
        $this->itemStep->setEventDispatcher($eventDispatcher);
        $this->itemStep->setJobRepository($jobRepository);
    }

    public function testExecute(): void
    {
        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects(self::any())
            ->method('getStatus')
            ->willReturn(new BatchStatus(BatchStatus::STARTING));
        $stepExecution->expects(self::any())
            ->method('getExitStatus')
            ->willReturn(new ExitStatus());

        $reader = $this->createMock(ReaderStub::class);
        $reader->expects(self::once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $reader->expects(self::exactly(8))
            ->method('read')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4, 5, 6, 7, null);

        $processor = $this->createMock(ProcessorStub::class);
        $processor->expects(self::once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $processor->expects(self::exactly(7))
            ->method('process')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4, 5, 6, 7);

        $writer = $this->createMock(WriterStub::class);
        $writer->expects(self::once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $writer->expects(self::exactly(2))
            ->method('write');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);
        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    public function testGetBatchSize(): void
    {
        self::assertNull($this->itemStep->getBatchSize());

        $batchSize = 100;
        $this->itemStep->setBatchSize($batchSize);

        self::assertSame($batchSize, $this->itemStep->getBatchSize());
    }
}

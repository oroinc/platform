<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\ExitStatus;
use Akeneo\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Akeneo\Bundle\BatchBundle\Tests\Unit\Step\Stub\ProcessorStub;
use Akeneo\Bundle\BatchBundle\Tests\Unit\Step\Stub\ReaderStub;
use Akeneo\Bundle\BatchBundle\Tests\Unit\Step\Stub\WriterStub;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ItemStepTest extends \PHPUnit\Framework\TestCase
{
    private const STEP_NAME = 'test_step_name';

    /** @var ItemStep */
    private $itemStep;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);

        $this->itemStep = new ItemStep(self::STEP_NAME);
        $this->itemStep->setEventDispatcher($this->eventDispatcher);
        $this->itemStep->setJobRepository($this->jobRepository);
    }

    public function testExecute()
    {
        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->willReturn(new BatchStatus(BatchStatus::STARTING));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->willReturn(new ExitStatus());

        $reader = $this->createMock(ReaderStub::class);
        $reader->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $reader->expects($this->exactly(8))
            ->method('read')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7, null));

        $processor = $this->createMock(ProcessorStub::class);
        $processor->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $processor->expects($this->exactly(7))
            ->method('process')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7));

        $writer = $this->createMock(WriterStub::class);
        $writer->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $writer->expects($this->exactly(2))
            ->method('write');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);
        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    public function testGetBatchSize(): void
    {
        $this->assertNull($this->itemStep->getBatchSize());

        $batchSize = 100;
        $this->itemStep->setBatchSize($batchSize);

        $this->assertSame($batchSize, $this->itemStep->getBatchSize());
    }
}

<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator;

class SelectiveContextAggregatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var SelectiveContextAggregator */
    private $aggregator;

    protected function setUp()
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->aggregator = new SelectiveContextAggregator($this->contextRegistry);
    }

    public function testGetType()
    {
        self::assertEquals(SelectiveContextAggregator::TYPE, $this->aggregator->getType());
    }

    public function testGetAggregatedContext()
    {
        $execution1Context = new ExecutionContext();
        $execution2Context = new ExecutionContext();
        $execution2Context->put(SelectiveContextAggregator::STEP_PARAMETER_NAME, true);
        $execution3Context = new ExecutionContext();
        $execution3Context->put(SelectiveContextAggregator::STEP_PARAMETER_NAME, false);
        $execution4Context = new ExecutionContext();
        $execution4Context->put(SelectiveContextAggregator::STEP_PARAMETER_NAME, true);

        $stepExecution1 = $this->createMock(StepExecution::class);
        $stepExecution2 = $this->createMock(StepExecution::class);
        $stepExecution3 = $this->createMock(StepExecution::class);
        $stepExecution4 = $this->createMock(StepExecution::class);
        $stepExecutions = new ArrayCollection();
        $stepExecutions->add($stepExecution1);
        $stepExecutions->add($stepExecution2);
        $stepExecutions->add($stepExecution3);
        $stepExecutions->add($stepExecution4);

        $stepExecution1->expects(self::once())
            ->method('getExecutionContext')
            ->willReturn($execution1Context);
        $stepExecution2->expects(self::once())
            ->method('getExecutionContext')
            ->willReturn($execution2Context);
        $stepExecution3->expects(self::once())
            ->method('getExecutionContext')
            ->willReturn($execution3Context);
        $stepExecution4->expects(self::once())
            ->method('getExecutionContext')
            ->willReturn($execution4Context);

        $stepExecution1Context = new Context([]);
        $stepExecution1Context->incrementReadCount();
        $stepExecution2Context = new Context([]);
        $stepExecution2Context->incrementReadCount();
        $stepExecution3Context = new Context([]);
        $stepExecution3Context->incrementReadCount();
        $stepExecution4Context = new Context([]);
        $stepExecution4Context->incrementReadCount();

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects(self::once())
            ->method('getStepExecutions')
            ->willReturn($stepExecutions);

        $this->contextRegistry->expects(self::at(0))
            ->method('getByStepExecution')
            ->with(self::identicalTo($stepExecution2))
            ->willReturn($stepExecution2Context);
        $this->contextRegistry->expects(self::at(1))
            ->method('getByStepExecution')
            ->with(self::identicalTo($stepExecution4))
            ->willReturn($stepExecution4Context);

        $result = $this->aggregator->getAggregatedContext($jobExecution);
        self::assertInstanceOf(ContextInterface::class, $result);
        self::assertSame(2, $result->getReadCount());
    }

    public function testGetAggregatedContextWhenStepExecutionsAreEmpty()
    {
        $stepExecutions = new ArrayCollection();

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects(self::once())
            ->method('getStepExecutions')
            ->willReturn($stepExecutions);

        self::assertNull($this->aggregator->getAggregatedContext($jobExecution));
    }
}

<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;

class SimpleContextAggregatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var SimpleContextAggregator */
    private $aggregator;

    protected function setUp()
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->aggregator = new SimpleContextAggregator($this->contextRegistry);
    }

    public function testGetType()
    {
        self::assertEquals(SimpleContextAggregator::TYPE, $this->aggregator->getType());
    }

    public function testGetAggregatedContext()
    {
        $stepExecution1 = $this->createMock(StepExecution::class);
        $stepExecution2 = $this->createMock(StepExecution::class);
        $stepExecutions = new ArrayCollection();
        $stepExecutions->add($stepExecution1);
        $stepExecutions->add($stepExecution2);

        $stepExecution1Context = new Context([]);
        $stepExecution1Context->incrementReadCount();
        $stepExecution2Context = new Context([]);
        $stepExecution2Context->incrementReadCount();
        $stepExecution2Context->incrementReadCount();

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects(self::once())
            ->method('getStepExecutions')
            ->willReturn($stepExecutions);

        $this->contextRegistry->expects(self::at(0))
            ->method('getByStepExecution')
            ->with(self::identicalTo($stepExecution1))
            ->willReturn($stepExecution1Context);
        $this->contextRegistry->expects(self::at(1))
            ->method('getByStepExecution')
            ->with(self::identicalTo($stepExecution2))
            ->willReturn($stepExecution2Context);

        $result = $this->aggregator->getAggregatedContext($jobExecution);
        self::assertInstanceOf(ContextInterface::class, $result);
        self::assertSame(3, $result->getReadCount());
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

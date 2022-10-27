<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Exception\JobInterruptedException;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;
use Oro\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Oro\Bundle\BatchBundle\Step\AbstractStep;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AbstractStepTest extends \PHPUnit\Framework\TestCase
{
    private const STEP_NAME = 'test_step_name';

    /** @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var AbstractStep|\PHPUnit\Framework\MockObject\MockObject */
    private $step;

    protected function setUp(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);

        $this->step = $this->getMockForAbstractClass(AbstractStep::class, [self::STEP_NAME]);
        $this->step->setEventDispatcher($eventDispatcher);
        $this->step->setJobRepository($this->jobRepository);
    }

    public function testGetSetJobRepository(): void
    {
        $this->step = $this->getMockForAbstractClass(AbstractStep::class, [self::STEP_NAME]);

        self::assertNull($this->step->getJobRepository());
        $this->step->setJobRepository($this->jobRepository);
        self::assertSame($this->jobRepository, $this->step->getJobRepository());
    }

    public function testGetSetName(): void
    {
        self::assertEquals(self::STEP_NAME, $this->step->getName());
        $this->step->setName('other_name');
        self::assertEquals('other_name', $this->step->getName());
    }

    public function testExecute(): void
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $stepExecution->expects(self::once())
            ->method('getExitStatus')
            ->willReturn(new ExitStatus(ExitStatus::COMPLETED));

        $stepExecution->expects(self::once())
            ->method('setEndTime')
            ->with(self::isInstanceOf(\DateTime::class));

        $stepExecution->expects(self::once())
            ->method('setExitStatus')
            ->with(self::equalTo(new ExitStatus(ExitStatus::COMPLETED)));

        $this->step->execute($stepExecution);
    }

    public function testExecuteWithTerminate(): void
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $stepExecution->expects(self::once())
            ->method('getExitStatus')
            ->willReturn(new ExitStatus(ExitStatus::COMPLETED));

        $stepExecution->expects(self::any())
            ->method('getStatus')
            ->willReturn(new BatchStatus(BatchStatus::STOPPED));

        $stepExecution->expects(self::any())
            ->method('isTerminateOnly')
            ->willReturn(true);

        $stepExecution->expects(self::once())
            ->method('upgradeStatus')
            ->with(BatchStatus::STOPPED);

        $stepExecution->expects(self::once())
            ->method('setExitStatus')
            ->with(new ExitStatus(ExitStatus::STOPPED, JobInterruptedException::class));

        $this->step->execute($stepExecution);
    }

    public function testExecuteWithError(): void
    {
        $exception = new \Exception('My exception');

        $this->step->expects(self::once())
            ->method('doExecute')
            ->willThrowException($exception);

        $stepExecution = $this->createMock(StepExecution::class);

        $stepExecution->expects(self::any())
            ->method('getStatus')
            ->willReturn(new BatchStatus(BatchStatus::FAILED));

        $stepExecution->expects(self::once())
            ->method('upgradeStatus')
            ->with(BatchStatus::FAILED);

        $stepExecution->expects(self::once())
            ->method('setExitStatus')
            ->with(new ExitStatus(ExitStatus::FAILED, $exception->getTraceAsString()));

        $this->step->execute($stepExecution);
    }
}

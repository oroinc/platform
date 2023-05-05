<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Job;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Exception\JobInterruptedException;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;
use Oro\Bundle\BatchBundle\Job\Job;
use Oro\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Oro\Bundle\BatchBundle\Step\AbstractStep;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\BatchBundle\Tests\Unit\Item\ItemProcessorTestHelper;
use Oro\Bundle\BatchBundle\Tests\Unit\Item\ItemReaderTestHelper;
use Oro\Bundle\BatchBundle\Tests\Unit\Item\ItemWriterTestHelper;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\IncompleteStep;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\InterruptedStep;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobTest extends \PHPUnit\Framework\TestCase
{
    private const JOB_TEST_NAME = 'job_test';

    /** @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var Job */
    private Job $job;

    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->job = new Job(self::JOB_TEST_NAME);
        $this->job->setEventDispatcher($this->eventDispatcher);
        $this->job->setJobRepository($this->jobRepository);
    }

    public function testGetName(): void
    {
        self::assertEquals(self::JOB_TEST_NAME, $this->job->getName());
    }

    public function testSetName(): void
    {
        $this->job->setName(self::JOB_TEST_NAME);
        self::assertEquals(self::JOB_TEST_NAME, $this->job->getName());
    }

    public function testExecute(): void
    {
        $beforeExecute = new \DateTime();

        $jobExecution = new JobExecution();

        self::assertNull($jobExecution->getStartTime());
        self::assertNull($jobExecution->getEndTime());
        self::assertEquals(BatchStatus::STARTING, $jobExecution->getStatus()->getValue());

        $this->job->setJobRepository($this->jobRepository);
        $this->job->execute($jobExecution);

        self::assertGreaterThanOrEqual(
            $beforeExecute,
            $jobExecution->getStartTime(),
            'Start time after test beginning'
        );
        self::assertGreaterThanOrEqual(
            $beforeExecute,
            $jobExecution->getEndTime(),
            'End time after test beginning'
        );
        self::assertGreaterThanOrEqual(
            $jobExecution->getStartTime(),
            $jobExecution->getEndTime(),
            'End time after start time'
        );

        // No step executed, must be not completed
        self::assertEquals(BatchStatus::STARTED, $jobExecution->getStatus()->getValue());
    }

    public function testExecuteException(): void
    {
        $exception = new \Exception('My test exception');

        $jobExecution = new JobExecution();

        $step = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step']);
        $step->setEventDispatcher($this->eventDispatcher);
        $step->setJobRepository($this->jobRepository);
        $step->expects(self::any())
            ->method('doExecute')
            ->willThrowException($exception);

        $this->job->addStep($step);

        $this->job->execute($jobExecution);

        self::assertEquals(BatchStatus::FAILED, $jobExecution->getStatus()->getValue());
        self::assertEquals(ExitStatus::FAILED, $jobExecution->getExitStatus()->getExitCode());
        self::assertStringStartsWith(
            $exception->getTraceAsString(),
            $jobExecution->getExitStatus()->getExitDescription()
        );
    }

    public function testExecuteStoppingWithNoStep(): void
    {
        $jobExecution = new JobExecution();
        $jobExecution->setStatus(new BatchStatus(BatchStatus::STOPPING));

        $this->job->setJobRepository($this->jobRepository);
        $this->job->execute($jobExecution);

        self::assertNull($jobExecution->getStartTime());
        self::assertEquals(BatchStatus::STOPPED, $jobExecution->getStatus()->getValue());
        self::assertEquals(ExitStatus::NOOP, $jobExecution->getExitStatus()->getExitCode());
    }

    public function testExecuteInterrupted(): void
    {
        $jobExecution = new JobExecution();

        $step = new InterruptedStep('my_interrupted_step');
        $step->setEventDispatcher($this->eventDispatcher);
        $step->setJobRepository($this->jobRepository);

        $this->job->setJobRepository($this->jobRepository);
        $this->job->addStep($step);
        $this->job->execute($jobExecution);

        self::assertEquals(BatchStatus::STOPPED, $jobExecution->getStatus()->getValue());
        self::assertEquals(
            ExitStatus::STOPPED,
            $jobExecution->getExitStatus()->getExitCode()
        );
        self::assertStringStartsWith(
            JobInterruptedException::class,
            $jobExecution->getExitStatus()->getExitDescription()
        );
    }

    public function testExecuteIncomplete(): void
    {
        $jobExecution = new JobExecution();

        $step = new IncompleteStep('my_incomplete_step');
        $step->setEventDispatcher($this->eventDispatcher);
        $step->setJobRepository($this->jobRepository);

        $this->job->setJobRepository($this->jobRepository);
        $this->job->addStep($step);
        $this->job->execute($jobExecution);

        self::assertEquals(BatchStatus::FAILED, $jobExecution->getStatus()->getValue());

        self::assertEquals(
            ExitStatus::COMPLETED,
            $jobExecution->getExitStatus()->getExitCode(),
            'Exit status code stopped'
        );
    }

    public function testToString(): void
    {
        self::assertEquals(get_class($this->job) . ': [name=' . self::JOB_TEST_NAME . ']', (string)$this->job);
    }

    public function testGetConfiguration(): void
    {
        $expectedConfiguration = [
            'reader_foo' => 'bar',
            'processor_foo' => 'bar',
            'writer_foo' => 'bar',
        ];

        $reader = $this->getReaderMock($expectedConfiguration, ['reader_foo']);
        $processor = $this->getProcessorMock($expectedConfiguration, ['processor_foo']);
        $writer = $this->getWriterMock($expectedConfiguration, ['writer_foo']);

        $step = $this->getItemStep('export', $reader, $processor, $writer);

        $this->job->addStep($step);

        self::assertEquals($expectedConfiguration, $this->job->getConfiguration());
    }

    public function testSetConfiguration(): void
    {
        $config = [
            'reader_foo' => 'reader_bar',
            'processor_foo' => 'processor_bar',
            'writer_foo' => 'writer_bar',
        ];

        $reader = $this->getReaderMock([], ['reader_foo']);
        $processor = $this->getProcessorMock([], ['processor_foo']);
        $writer = $this->getWriterMock([], ['writer_foo']);

        $reader->expects(self::once())
            ->method('setConfiguration')
            ->with($config);

        $processor->expects(self::once())
            ->method('setConfiguration')
            ->with($config);

        $writer->expects(self::once())
            ->method('setConfiguration')
            ->with($config);

        $itemStep = $this->getItemStep('export', $reader, $processor, $writer);
        $this->job->addStep($itemStep);
        $this->job->setConfiguration($config);
    }

    public function testAddStep(): void
    {
        $step1 = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step1']);
        $step2 = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step2']);

        $this->job->addStep($step1);
        $this->job->addStep($step2);

        self::assertEquals([$step1, $step2], $this->job->getSteps());
    }

    public function testSetSteps(): void
    {
        $step1 = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step1']);
        $step2 = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step2']);

        $this->job->setSteps([$step1, $step2]);

        self::assertEquals([$step1, $step2], $this->job->getSteps());
    }

    public function testGetStepNames(): void
    {
        $step1 = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step1']);
        $step2 = $this->getMockForAbstractClass(AbstractStep::class, ['my_mock_step2']);

        $this->job->setSteps([$step1, $step2]);

        self::assertEquals(['my_mock_step1', 'my_mock_step2'], $this->job->getStepNames());
    }

    public function getItemStep($name, $reader, $processor, $writer): ItemStep
    {
        $itemStep = new ItemStep($name);
        $itemStep->setReader($reader);
        $itemStep->setProcessor($processor);
        $itemStep->setWriter($writer);

        return $itemStep;
    }

    private function getReaderMock(
        array $configuration,
        array $fields = []
    ): \PHPUnit\Framework\MockObject\MockObject|ItemReaderTestHelper {
        $reader = $this->createMock(ItemReaderTestHelper::class);
        $reader->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $reader->expects(self::any())
            ->method('getConfigurationFields')
            ->willReturn($fields);

        return $reader;
    }

    private function getProcessorMock(
        array $configuration,
        array $fields = []
    ): ItemProcessorTestHelper|\PHPUnit\Framework\MockObject\MockObject {
        $processor = $this->createMock(ItemProcessorTestHelper::class);
        $processor->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $processor->expects(self::any())
            ->method('getConfigurationFields')
            ->willReturn($fields);

        return $processor;
    }

    private function getWriterMock(
        array $configuration,
        array $fields = []
    ): ItemWriterTestHelper|\PHPUnit\Framework\MockObject\MockObject {
        $writer = $this->createMock(ItemWriterTestHelper::class);
        $writer->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $writer->expects(self::any())
            ->method('getConfigurationFields')
            ->willReturn($fields);

        return $writer;
    }
}

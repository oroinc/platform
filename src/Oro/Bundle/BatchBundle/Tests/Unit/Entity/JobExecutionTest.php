<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobExecutionTest extends \PHPUnit\Framework\TestCase
{
    private JobExecution $jobExecution;

    protected function setUp(): void
    {
        $this->jobExecution = new JobExecution();
    }

    public function testGetId(): void
    {
        self::assertNull($this->jobExecution->getId());
    }

    public function testGetSetEndTime(): void
    {
        self::assertNull($this->jobExecution->getEndTime());

        $expectedEndTime = new \DateTime();
        $this->assertEntity($this->jobExecution->setEndTime($expectedEndTime));
        self::assertEquals($expectedEndTime, $this->jobExecution->getEndTime());
    }

    public function testGetSetStartTime(): void
    {
        self::assertNull($this->jobExecution->getStartTime());

        $expectedStartTime = new \DateTime();
        $this->assertEntity($this->jobExecution->setStartTime($expectedStartTime));
        self::assertEquals($expectedStartTime, $this->jobExecution->getStartTime());
    }

    public function testGetSetCreateTime(): void
    {
        self::assertNotNull($this->jobExecution->getCreateTime());

        $expectedCreateTime = new \DateTime();
        $this->assertEntity($this->jobExecution->setCreateTime($expectedCreateTime));
        self::assertEquals($expectedCreateTime, $this->jobExecution->getCreateTime());
    }

    public function testGetSetUpdatedTime(): void
    {
        self::assertNull($this->jobExecution->getUpdatedTime());

        $expectedUpdatedTime = new \DateTime();
        $this->assertEntity($this->jobExecution->setUpdatedTime($expectedUpdatedTime));
        self::assertEquals($expectedUpdatedTime, $this->jobExecution->getUpdatedTime());
    }

    public function testGetSetStatus(): void
    {
        self::assertEquals(new BatchStatus(BatchStatus::STARTING), $this->jobExecution->getStatus());

        $expectedBatchStatus = new BatchStatus(BatchStatus::COMPLETED);

        $this->assertEntity($this->jobExecution->setStatus($expectedBatchStatus));
        self::assertEquals($expectedBatchStatus, $this->jobExecution->getStatus());
    }

    public function testUpgradeStatus(): void
    {
        $expectedBatchStatus = new BatchStatus(BatchStatus::STARTED);
        $this->jobExecution->setStatus($expectedBatchStatus);

        $expectedBatchStatus->upgradeTo(BatchStatus::COMPLETED);

        $this->assertEntity($this->jobExecution->upgradeStatus(BatchStatus::COMPLETED));
        self::assertEquals($expectedBatchStatus, $this->jobExecution->getStatus());
    }

    public function testGetSetExitStatus(): void
    {
        self::assertEquals(new ExitStatus(ExitStatus::UNKNOWN), $this->jobExecution->getExitStatus());

        $expectedExitStatus = new ExitStatus(ExitStatus::COMPLETED);

        $this->assertEntity($this->jobExecution->setExitStatus($expectedExitStatus));
        self::assertEquals($expectedExitStatus, $this->jobExecution->getExitStatus());
    }

    public function testGetSetExecutionContext(): void
    {
        self::assertEquals(new ExecutionContext(), $this->jobExecution->getExecutionContext());

        $expectedExecutionContext = new ExecutionContext();
        $expectedExecutionContext->put('key', 'value');

        $this->assertEntity($this->jobExecution->setExecutionContext($expectedExecutionContext));
        self::assertEquals($expectedExecutionContext, $this->jobExecution->getExecutionContext());
    }

    public function testStepExecutions(): void
    {
        self::assertEquals(0, $this->jobExecution->getStepExecutions()->count());

        $jobExecution = new JobExecution();

        $stepExecution1 = new StepExecution('my_step_name_1', $jobExecution);
        $this->jobExecution->addStepExecution($stepExecution1);

        self::assertEquals(
            new ArrayCollection([$stepExecution1]),
            $this->jobExecution->getStepExecutions()
        );

        $stepExecution2 = $this->jobExecution->createStepExecution('my_step_name_2');

        self::assertEquals(
            new ArrayCollection([$stepExecution1, $stepExecution2]),
            $this->jobExecution->getStepExecutions()
        );
    }

    public function testIsRunning(): void
    {
        self::assertTrue($this->jobExecution->isRunning());

        $status = $this->createMock(BatchStatus::class);
        $status->expects(self::any())
            ->method('getValue')
            ->willReturn(BatchStatus::COMPLETED);
        $this->jobExecution->setStatus($status);

        self::assertFalse($this->jobExecution->isRunning());
    }

    public function testIsStopping(): void
    {
        self::assertFalse($this->jobExecution->isStopping());
        $this->jobExecution->upgradeStatus(BatchStatus::STOPPING);
        self::assertTrue($this->jobExecution->isStopping());
    }

    public function testStop(): void
    {
        self::assertFalse($this->jobExecution->isStopping());
        $this->jobExecution->stop();
        self::assertTrue($this->jobExecution->isStopping());
    }

    public function testStopWithStepExecutions(): void
    {
        self::assertFalse($this->jobExecution->isStopping());
        $this->jobExecution->createStepExecution('my_step_name_2');
        $this->assertEntity($this->jobExecution->stop());
        self::assertTrue($this->jobExecution->isStopping());
    }

    public function testGetAddFailureExceptions(): void
    {
        self::assertEmpty($this->jobExecution->getFailureExceptions());

        $exception1 = new \Exception('My exception 1', 1);
        $exception2 = new \Exception('My exception 2', 2);

        $this->assertEntity($this->jobExecution->addFailureException($exception1));
        $this->assertEntity($this->jobExecution->addFailureException($exception2));

        $failureExceptions = $this->jobExecution->getFailureExceptions();

        self::assertEquals('Exception', $failureExceptions[0]['class']);
        self::assertEquals('My exception 1', $failureExceptions[0]['message']);
        self::assertEquals('1', $failureExceptions[0]['code']);
        self::assertStringContainsString(__FUNCTION__, $failureExceptions[0]['trace']);

        self::assertEquals('Exception', $failureExceptions[1]['class']);
        self::assertEquals('My exception 2', $failureExceptions[1]['message']);
        self::assertEquals('2', $failureExceptions[1]['code']);
        self::assertStringContainsString(__FUNCTION__, $failureExceptions[1]['trace']);
    }

    public function testGetAllFailureExceptions(): void
    {
        self::assertEmpty($this->jobExecution->getAllFailureExceptions());

        $stepExecution = $this->jobExecution->createStepExecution('my_step_name_2');
        $exception1 = new \Exception('My exception 1', 1);
        $exception2 = new \Exception('My exception 2', 2);
        $stepException = new \Exception('My step exception 1', 100);

        $this->jobExecution->addFailureException($exception1);
        $this->jobExecution->addFailureException($exception2);
        $stepExecution->addFailureException($stepException);

        $allFailureExceptions = $this->jobExecution->getAllFailureExceptions();

        self::assertEquals('Exception', $allFailureExceptions[0]['class']);
        self::assertEquals('My exception 1', $allFailureExceptions[0]['message']);
        self::assertEquals('1', $allFailureExceptions[0]['code']);
        self::assertStringContainsString(__FUNCTION__, $allFailureExceptions[0]['trace']);

        self::assertEquals('Exception', $allFailureExceptions[1]['class']);
        self::assertEquals('My exception 2', $allFailureExceptions[1]['message']);
        self::assertEquals('2', $allFailureExceptions[1]['code']);
        self::assertStringContainsString(__FUNCTION__, $allFailureExceptions[1]['trace']);

        self::assertEquals('Exception', $allFailureExceptions[2]['class']);
        self::assertEquals('My step exception 1', $allFailureExceptions[2]['message']);
        self::assertEquals('100', $allFailureExceptions[2]['code']);
        self::assertStringContainsString(__FUNCTION__, $allFailureExceptions[2]['trace']);
    }

    public function testSetGetJobInstance(): void
    {
        self::assertNull($this->jobExecution->getJobInstance());
        $jobInstance = new JobInstance('test_connector', JobInstance::TYPE_IMPORT, 'test_job_instance');
        $this->assertEntity($this->jobExecution->setJobInstance($jobInstance));
        self::assertSame($jobInstance, $this->jobExecution->getJobInstance());
    }

    public function testGetLabel(): void
    {
        $jobInstance = $this->createMock(JobInstance::class);
        $this->jobExecution->setJobInstance($jobInstance);

        $jobInstance->expects(self::any())->method('getLabel')->willReturn('foo');

        self::assertEquals('foo', $this->jobExecution->getLabel());
    }

    public function testToString(): void
    {
        $startTime = new \DateTime('2013-02-01 12:34:56');
        $updatedTime = new \DateTime('2013-02-03 23:45:01');
        $status = BatchStatus::STOPPED;
        $exitStatus = ExitStatus::FAILED;
        $jobInstance = new JobInstance('test_connector', JobInstance::TYPE_IMPORT, 'test_job_instance');
        $jobInstance->setCode('job instance code');
        $endTime = new \DateTime('2013-03-04 21:43:05');

        $this->jobExecution->setStartTime($startTime);
        $this->jobExecution->setUpdatedTime($updatedTime);
        $this->jobExecution->setStatus(new BatchStatus($status));
        $this->jobExecution->setExitStatus(new ExitStatus($exitStatus, 'Test description'));
        $this->jobExecution->setJobInstance($jobInstance);
        $this->jobExecution->setEndTime($endTime);

        $timezone = $startTime->format('P');

        $expectedOutput = 'startTime=2013-02-01T12:34:56' . $timezone . ', endTime=2013-03-04T21:43:05' .
            $timezone . ', ' . 'updatedTime=2013-02-03T23:45:01' . $timezone . ', status=5, exitStatus=[FAILED] ' .
            'Test description, exitDescription=[Test description], job=[job instance code]';

        self::assertEquals($expectedOutput, (string)$this->jobExecution);
    }

    public function testToStringEmpty(): void
    {
        $expectedOutput = 'startTime=, endTime=, updatedTime=, status=2, ' .
            'exitStatus=[UNKNOWN] , exitDescription=[], job=[]';

        self::assertEquals($expectedOutput, (string)$this->jobExecution);
    }

    private function assertEntity(object $entity): void
    {
        self::assertInstanceOf(JobExecution::class, $entity);
    }
}

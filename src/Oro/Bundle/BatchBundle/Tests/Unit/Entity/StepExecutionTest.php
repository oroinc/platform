<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Entity;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StepExecutionTest extends \PHPUnit\Framework\TestCase
{
    private JobExecution $jobExecution;
    private StepExecution $stepExecution;

    protected function setUp(): void
    {
        $this->jobExecution = new JobExecution();
        $this->stepExecution = new StepExecution('my_step_execution', $this->jobExecution);
    }

    public function testGetId(): void
    {
        self::assertNull($this->stepExecution->getId());
    }

    public function testGetSetEndTime(): void
    {
        self::assertNull($this->stepExecution->getEndTime());

        $expectedEndTime = new \DateTime();
        $this->stepExecution->setEndTime($expectedEndTime);
        self::assertEquals($expectedEndTime, $this->stepExecution->getEndTime());
    }

    public function testGetSetStartTime(): void
    {
        $afterConstruct = new \DateTime();
        self::assertLessThanOrEqual($afterConstruct, $this->stepExecution->getStartTime());

        $expectedStartTime = new \DateTime();
        $this->stepExecution->setStartTime($expectedStartTime);
        self::assertEquals($expectedStartTime, $this->stepExecution->getStartTime());
    }

    public function testGetSetStatus(): void
    {
        self::assertEquals(new BatchStatus(BatchStatus::STARTING), $this->stepExecution->getStatus());

        $expectedBatchStatus = new BatchStatus(BatchStatus::COMPLETED);

        $this->stepExecution->setStatus($expectedBatchStatus);
        self::assertEquals($expectedBatchStatus, $this->stepExecution->getStatus());
    }

    public function testUpgradeStatus(): void
    {
        $expectedBatchStatus = new BatchStatus(BatchStatus::STARTED);
        $this->stepExecution->setStatus($expectedBatchStatus);

        $expectedBatchStatus->upgradeTo(BatchStatus::COMPLETED);

        $this->stepExecution->upgradeStatus(BatchStatus::COMPLETED);
        self::assertEquals($expectedBatchStatus, $this->stepExecution->getStatus());
    }

    public function testGetSetExitStatus(): void
    {
        self::assertEquals(new ExitStatus(ExitStatus::EXECUTING), $this->stepExecution->getExitStatus());

        $expectedExitStatus = new ExitStatus(ExitStatus::COMPLETED);

        $this->stepExecution->setExitStatus($expectedExitStatus);
        self::assertEquals($expectedExitStatus, $this->stepExecution->getExitStatus());
    }

    public function testGetSetExecutionContext(): void
    {
        self::assertEquals(new ExecutionContext(), $this->stepExecution->getExecutionContext());

        $expectedExecutionContext = new ExecutionContext();
        $expectedExecutionContext->put('key', 'value');

        $this->stepExecution->setExecutionContext($expectedExecutionContext);
        self::assertSame($expectedExecutionContext, $this->stepExecution->getExecutionContext());
    }

    public function testGetAddFailureExceptions(): void
    {
        self::assertEmpty($this->stepExecution->getFailureExceptions());

        $exception1 = new \Exception('My exception 1', 1);
        $exception2 = new \Exception('My exception 2', 2);

        $this->stepExecution->addFailureException($exception1);
        $this->stepExecution->addFailureException($exception2);

        $failureExceptions = $this->stepExecution->getFailureExceptions();

        self::assertEquals('Exception', $failureExceptions[0]['class']);
        self::assertEquals('My exception 1', $failureExceptions[0]['message']);
        self::assertEquals('1', $failureExceptions[0]['code']);
        self::assertStringContainsString(__FUNCTION__, $failureExceptions[0]['trace']);

        self::assertEquals('Exception', $failureExceptions[1]['class']);
        self::assertEquals('My exception 2', $failureExceptions[1]['message']);
        self::assertEquals('2', $failureExceptions[1]['code']);
        self::assertStringContainsString(__FUNCTION__, $failureExceptions[1]['trace']);
    }

    public function testGetSetReadCount(): void
    {
        self::assertEquals(0, $this->stepExecution->getReadCount());
        $this->stepExecution->setReadCount(8);
        self::assertEquals(8, $this->stepExecution->getReadCount());
    }

    public function testGetSetWriteCount(): void
    {
        self::assertEquals(0, $this->stepExecution->getWriteCount());
        $this->stepExecution->setWriteCount(6);
        self::assertEquals(6, $this->stepExecution->getWriteCount());
    }

    public function testGetSetFilterCount(): void
    {
        $this->stepExecution->setReadCount(10);
        $this->stepExecution->setWriteCount(5);
        self::assertEquals(5, $this->stepExecution->getFilterCount());
    }

    public function testTerminateOnly(): void
    {
        self::assertFalse($this->stepExecution->isTerminateOnly());
        $this->stepExecution->setTerminateOnly();
        self::assertTrue($this->stepExecution->isTerminateOnly());
    }

    public function testGetStepName(): void
    {
        self::assertEquals('my_step_execution', $this->stepExecution->getStepName());
    }

    public function testGetJobExecution(): void
    {
        self::assertSame($this->jobExecution, $this->stepExecution->getJobExecution());
    }

    public function testToString(): void
    {
        $expectedString = 'id=0, name=[my_step_execution], status=[2], exitCode=[EXECUTING], exitDescription=[]';
        self::assertEquals($expectedString, (string)$this->stepExecution);
    }

    public function testAddWarning(): void
    {
        $getWarnings = static fn ($warnings) => array_map(
            static fn ($warning) => $warning->toArray(),
            $warnings->toArray()
        );

        $this->stepExecution->addWarning(
            'foo',
            '%something% is wrong on line 1',
            ['%something%' => 'Item1'],
            ['foo' => 'bar']
        );
        $this->stepExecution->addWarning(
            'bar',
            '%something% is wrong on line 2',
            ['%something%' => 'Item2'],
            ['baz' => false]
        );
        $item = new \stdClass();
        $this->stepExecution->addWarning(
            'baz',
            '%something% is wrong with object 3',
            ['%something%' => 'Item3'],
            $item
        );

        self::assertEquals(
            [
                [
                    'name' => 'my_step_execution.steps.foo.title',
                    'reason' => '%something% is wrong on line 1',
                    'reasonParameters' => ['%something%' => 'Item1'],
                    'item' => ['foo' => 'bar'],
                ],
                [
                    'name' => 'my_step_execution.steps.bar.title',
                    'reason' => '%something% is wrong on line 2',
                    'reasonParameters' => ['%something%' => 'Item2'],
                    'item' => ['baz' => false],
                ],
                [
                    'name' => 'my_step_execution.steps.baz.title',
                    'reason' => '%something% is wrong with object 3',
                    'reasonParameters' => ['%something%' => 'Item3'],
                    'item' => ['id' => '[unknown]', 'class' => 'stdClass', 'string' => '[unknown]'],
                ],
            ],
            $getWarnings($this->stepExecution->getWarnings())
        );

        $stepExecution = new StepExecution('my_step_execution.foobarbaz', $this->jobExecution);
        $stepExecution->addWarning(
            'foo',
            '%something% is wrong on line 1',
            ['%something%' => 'Item1'],
            ['foo' => 'bar']
        );
        $stepExecution->addWarning(
            'bar',
            '%something% is wrong on line 2',
            ['%something%' => 'Item2'],
            ['baz' => false]
        );

        self::assertEquals(
            [
                [
                    'name' => 'my_step_execution.steps.foo.title',
                    'reason' => '%something% is wrong on line 1',
                    'reasonParameters' => ['%something%' => 'Item1'],
                    'item' => ['foo' => 'bar'],
                ],
                [
                    'name' => 'my_step_execution.steps.bar.title',
                    'reason' => '%something% is wrong on line 2',
                    'reasonParameters' => ['%something%' => 'Item2'],
                    'item' => ['baz' => false],
                ],
            ],
            $getWarnings($stepExecution->getWarnings())
        );
    }

    public function testIncrementSummaryInfoByOne(): void
    {
        $this->stepExecution->incrementSummaryInfo('create');
        $this->stepExecution->incrementSummaryInfo('create');
        self::assertEquals(2, $this->stepExecution->getSummaryInfo('create'));
        $this->stepExecution->incrementSummaryInfo('create');
        self::assertEquals(3, $this->stepExecution->getSummaryInfo('create'));
    }

    public function testIncrementSummaryInfoByBulk(): void
    {
        $this->stepExecution->incrementSummaryInfo('create');
        $this->stepExecution->incrementSummaryInfo('create');
        self::assertEquals(2, $this->stepExecution->getSummaryInfo('create'));
        $this->stepExecution->incrementSummaryInfo('create', 5);
        self::assertEquals(7, $this->stepExecution->getSummaryInfo('create'));
    }
}

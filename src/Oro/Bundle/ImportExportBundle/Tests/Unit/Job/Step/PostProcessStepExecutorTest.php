<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Step;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Job\Step\PostProcessStepExecutor;

class PostProcessStepExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobExecutor;

    /** @var ItemReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var ItemProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var ItemWriterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $writer;

    /** @var PostProcessStepExecutor */
    private $executor;

    protected function setUp(): void
    {
        $this->jobExecutor = $this->createMock(JobExecutor::class);
        $this->reader = $this->createMock(ItemReaderInterface::class);
        $this->processor = $this->createMock(ItemProcessorInterface::class);
        $this->writer = $this->createMock(ItemWriterInterface::class);

        $this->executor = new PostProcessStepExecutor();
        $this->executor->setJobExecutor($this->jobExecutor);
        $this->executor->setReader($this->reader);
        $this->executor->setProcessor($this->processor);
        $this->executor->setWriter($this->writer);
        $this->executor->setBatchSize(2);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $contextSharedKeys,
        array $context,
        array $job,
        bool $isJobSuccess = true,
        int $jobExecutions = 0,
        array $expectedContext = []
    ) {
        if ($job) {
            [$jobType, $jobName] = $job;

            $this->executor->addPostProcessingJob($jobType, $jobName);
        }

        $this->executor->setContextSharedKeys($contextSharedKeys);

        $stepExecution = $this->createMock(StepExecution::class);

        $executionContext = new ExecutionContext();
        foreach ($context as $key => $value) {
            $executionContext->put($key, $value);
        }

        $jobExecution = $this->createMock(JobExecution::class);
        $jobInstance = $this->createMock(JobInstance::class);
        $jobExecution->expects($this->any())
            ->method('getJobInstance')
            ->willReturn($jobInstance);
        $jobExecution->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($executionContext);
        $stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($jobExecution);

        $this->executor->setStepExecution($stepExecution);

        $jobResult = new JobResult();
        $jobResult->setSuccessful($isJobSuccess);
        if (!$isJobSuccess) {
            $this->expectException(RuntimeException::class);
        }

        $this->jobExecutor->expects($this->exactly($jobExecutions))
            ->method('executeJob')
            ->willReturn($jobResult);

        $this->processor->expects($this->any())
            ->method('process')
            ->willReturnArgument(0);
        $this->reader->expects($this->atLeastOnce())
            ->method('read')
            ->willReturnOnConsecutiveCalls(new \stdClass(), new \stdClass(), new \stdClass(), null);

        $this->executor->execute();

        $this->assertEquals($expectedContext, $executionContext->getKeys());
    }

    public function executeDataProvider(): array
    {
        return [
            'empty keys' => [[], [], []],
            'defined key with empty context' => [['some-key'], [], []],
            'defined key' => [['some-key'], ['some-key' => 'value'], []],
            'defined key with post process job' => [
                ['some-key'],
                ['some-key' => ['value', 'value1'], 'another-key' => ['next-value']],
                ['jobType', 'jobName'],
                true,
                1,
                ['another-key']
            ],
            'defined key with post process job but its failed' => [
                ['some-key'],
                ['some-key' => ['value', 'value1'], 'another-key' => ['next-value']],
                ['jobType', 'jobName'],
                false,
                1,
                ['another-key']
            ]
        ];
    }
}

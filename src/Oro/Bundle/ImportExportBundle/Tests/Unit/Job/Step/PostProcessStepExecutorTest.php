<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Step;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Job\Step\PostProcessStepExecutor;

class PostProcessStepExecutorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PostProcessStepExecutor
     */
    protected $executor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ItemReaderInterface
     */
    protected $reader;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ItemProcessorInterface
     */
    protected $processor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ItemWriterInterface
     */
    protected $writer;

    protected function setUp(): void
    {
        $this->executor = new PostProcessStepExecutor();

        $this->jobExecutor = $this->getMockBuilder(JobExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->executor->setJobExecutor($this->jobExecutor);

        $this->reader = $this->createMock(ItemReaderInterface::class);
        $this->executor->setReader($this->reader);

        $this->processor = $this->createMock(ItemProcessorInterface::class);
        $this->executor->setProcessor($this->processor);

        $this->writer = $this->createMock(ItemWriterInterface::class);
        $this->executor->setWriter($this->writer);

        $this->executor->setBatchSize(2);
    }

    /**
     * @param array $contextSharedKeys
     * @param array $context
     * @param array $job
     * @param bool $isJobSuccess
     * @param int $jobExecutions
     * @param array $expectedContext
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $contextSharedKeys,
        array $context,
        array $job,
        $isJobSuccess = true,
        $jobExecutions = 0,
        $expectedContext = []
    ) {
        if ($job) {
            [$jobType, $jobName] = $job;

            $this->executor->addPostProcessingJob($jobType, $jobName);
        }

        $this->executor->setContextSharedKeys($contextSharedKeys);

        /** @var \PHPUnit\Framework\MockObject\MockObject|StepExecution $stepExecution */
        $stepExecution = $this->getMockBuilder(StepExecution::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContext = new ExecutionContext();
        foreach ($context as $key => $value) {
            $executionContext->put($key, $value);
        }

        $jobExecution = $this->createMock(JobExecution::class);
        $jobInstance = $this->createMock(JobInstance::class);
        $jobExecution->expects($this->any())
            ->method('getJobInstance')
            ->will($this->returnValue($jobInstance));
        $jobExecution->expects($this->any())
            ->method('getExecutionContext')
            ->will($this->returnValue($executionContext));
        $stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->will($this->returnValue($jobExecution));

        $this->executor->setStepExecution($stepExecution);

        $jobResult = new JobResult();
        $jobResult->setSuccessful($isJobSuccess);
        if (!$isJobSuccess) {
            $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\RuntimeException::class);
        }

        $this->jobExecutor->expects($this->exactly($jobExecutions))
            ->method('executeJob')
            ->willReturn($jobResult);

        $this->processor->expects($this->any())->method('process')->willReturnArgument(0);
        $this->reader->expects($this->atLeastOnce())->method('read')
            ->willReturnOnConsecutiveCalls(new \stdClass(), new \stdClass(), new \stdClass(), null);

        $this->executor->execute();

        $this->assertEquals($expectedContext, $executionContext->getKeys());
    }

    /**
     * @return array
     */
    public function executeDataProvider()
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

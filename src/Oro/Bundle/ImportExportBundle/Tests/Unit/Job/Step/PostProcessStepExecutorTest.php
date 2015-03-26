<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Job\Step\PostProcessStepExecutor;

class PostProcessStepExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PostProcessStepExecutor
     */
    protected $executor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|JobExecutor
     */
    protected $jobExecutor;

    protected function setUp()
    {
        $this->executor = new PostProcessStepExecutor();

        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Job\JobExecutor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->executor->setJobExecutor($this->jobExecutor);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ItemReaderInterface $reader */
        $reader = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface');
        $this->executor->setReader($reader);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ItemProcessorInterface $processor */
        $processor = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface');
        $this->executor->setProcessor($processor);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ItemWriterInterface $writer */
        $writer = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface');
        $this->executor->setWriter($writer);
    }

    /**
     * @param array $contextSharedKeys
     * @param array $context
     * @param array $job
     * @param bool $isJobSuccess
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $contextSharedKeys, array $context, array $job, $isJobSuccess = true)
    {
        if ($job) {
            list($jobType, $jobName) = $job;

            $this->executor->addPostProcessingJob($jobType, $jobName);
        }

        $this->executor->setContextSharedKeys($contextSharedKeys);

        /** @var \PHPUnit_Framework_MockObject_MockObject|StepExecution $stepExecution */
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $executionContext = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');
        $executionContext->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($key) use ($context) {
                        if (array_key_exists($key, $context)) {
                            return $context[$key];
                        }

                        return null;
                    }
                )
            );
        $executionContext->expects($this->any())
            ->method('getKeys')
            ->willReturn(array_keys($context));
        $jobExecution = $this->getMock('Akeneo\Bundle\BatchBundle\Entity\JobExecution');
        $jobInstance = $this->getMock('Akeneo\Bundle\BatchBundle\Entity\JobInstance');
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
            $this->setExpectedException('Oro\Bundle\ImportExportBundle\Exception\RuntimeException');
        }

        $this->jobExecutor->expects($this->any())
            ->method('executeJob')
            ->willReturn($jobResult);

        $this->executor->execute();
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
                ['some-key' => 'value', 'another-key' => 'next-value'],
                ['jobType', 'jobName']
            ],
            'defined key with post process job but its failed' => [
                ['some-key'],
                ['some-key' => 'value', 'another-key' => 'next-value'],
                ['jobType', 'jobName'],
                false
            ]
        ];
    }
}

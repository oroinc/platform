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
use Oro\Bundle\ImportExportBundle\Job\Step\PostProcessItemStep;

class PostProcessItemStepTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PostProcessItemStep
     */
    protected $itemStep;

    protected function setUp(): void
    {
        $this->itemStep = new PostProcessItemStep('step_name');

        /** @var \PHPUnit\Framework\MockObject\MockObject|JobExecutor $jobExecutor */
        $jobExecutor = $this->getMockBuilder(JobExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemStep->setJobExecutor($jobExecutor);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ItemReaderInterface $reader */
        $reader = $this->createMock(ItemReaderInterface::class);
        $this->itemStep->setReader($reader);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ItemProcessorInterface $processor */
        $processor = $this->createMock(ItemProcessorInterface::class);
        $this->itemStep->setProcessor($processor);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ItemWriterInterface $writer */
        $writer = $this->createMock(ItemWriterInterface::class);
        $this->itemStep->setWriter($writer);

        $this->itemStep->setBatchSize(1);
    }

    /**
     * @param string $jobName
     * @param string $contextKeys
     *
     * @dataProvider executeDataProvider
     */
    public function testDoExecute($jobName, $contextKeys)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|StepExecution $stepExecution */
        $stepExecution = $this->getMockBuilder(StepExecution::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContext = $this->createMock(ExecutionContext::class);
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

        $this->itemStep->setPostProcessingJobs($jobName);
        $jobInstance->expects($jobName ? $this->once() : $this->never())
            ->method('getType')
            ->willReturn('export');

        $this->itemStep->setContextSharedKeys($contextKeys);

        $this->itemStep->doExecute($stepExecution);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'empty job' => [null, null],
            'invalid job' => ['', ''],
            'int names' => [1, 1],
            'single job' => ['job_name', 'context_key'],
            'multiple jobs' => ['job_name, job_name2', 'context_key ,another_context_key']
        ];
    }
}

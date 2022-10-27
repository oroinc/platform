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
    /** @var PostProcessItemStep */
    private $itemStep;

    protected function setUp(): void
    {
        $this->itemStep = new PostProcessItemStep('step_name');
        $this->itemStep->setJobExecutor($this->createMock(JobExecutor::class));
        $this->itemStep->setReader($this->createMock(ItemReaderInterface::class));
        $this->itemStep->setProcessor($this->createMock(ItemProcessorInterface::class));
        $this->itemStep->setWriter($this->createMock(ItemWriterInterface::class));
        $this->itemStep->setBatchSize(1);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testDoExecute(string $jobName, string $contextKeys)
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $executionContext = $this->createMock(ExecutionContext::class);
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

        $this->itemStep->setPostProcessingJobs($jobName);
        $jobInstance->expects($jobName ? $this->once() : $this->never())
            ->method('getType')
            ->willReturn('export');

        $this->itemStep->setContextSharedKeys($contextKeys);

        $this->itemStep->doExecute($stepExecution);
    }

    public function executeDataProvider(): array
    {
        return [
            'invalid job' => ['', ''],
            'single job' => ['job_name', 'context_key'],
            'multiple jobs' => ['job_name, job_name2', 'context_key ,another_context_key']
        ];
    }
}

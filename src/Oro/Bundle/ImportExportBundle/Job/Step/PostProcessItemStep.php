<?php

namespace Oro\Bundle\ImportExportBundle\Job\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;

class PostProcessItemStep extends ItemStep
{
    /**
     * @var array
     */
    protected $postProcessingJobs = [];

    /**
     * @var array
     */
    protected $contextSharedKeys = [];

    /**
     * @var JobExecutor
     */
    protected $jobExecutor;

    /**
     * @param string $jobName
     */
    public function setPostProcessingJobs($jobName)
    {
        $this->postProcessingJobs = $this->scalarToArray($jobName);
    }

    /**
     * @param string $contextSharedKeys
     */
    public function setContextSharedKeys($contextSharedKeys)
    {
        $this->contextSharedKeys = $this->scalarToArray($contextSharedKeys);
    }

    /**
     * @param JobExecutor $jobExecutor
     */
    public function setJobExecutor(JobExecutor $jobExecutor)
    {
        $this->jobExecutor = $jobExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        $this->initializeStepElements($stepExecution);

        $stepExecutor = new PostProcessStepExecutor();
        $stepExecutor
            ->setStepExecution($stepExecution)
            ->setJobExecutor($this->jobExecutor)
            ->setReader($this->reader)
            ->setProcessor($this->processor)
            ->setWriter($this->writer);
        if (null !== $this->batchSize) {
            $stepExecutor->setBatchSize($this->batchSize);
        }

        if ($this->contextSharedKeys) {
            $stepExecutor->setContextSharedKeys($this->contextSharedKeys);
        }

        if ($this->postProcessingJobs) {
            $jobType = $stepExecution->getJobExecution()->getJobInstance()->getType();
            foreach ($this->postProcessingJobs as $jobName) {
                $stepExecutor->addPostProcessingJob($jobType, $jobName);
            }
        }

        $stepExecutor->execute($this);
        $this->flushStepElements();
    }

    /**
     * @param string $scalar
     * @return array
     */
    protected function scalarToArray($scalar)
    {
        $result = explode(',', $scalar);
        $result = array_map('trim', $result);

        return array_filter($result);
    }
}

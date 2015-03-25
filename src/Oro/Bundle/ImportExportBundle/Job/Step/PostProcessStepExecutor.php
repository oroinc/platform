<?php

namespace Oro\Bundle\ImportExportBundle\Job\Step;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

class PostProcessStepExecutor extends StepExecutor implements StepExecutionAwareInterface
{
    const JOB_TYPE_KEY = 'type';
    const JOB_NAME_KEY = 'name';

    /**
     * @var array
     */
    protected $postProcessingJobs = [];

    /**
     * @var JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var array
     */
    protected $contextSharedKeys = [];

    /**
     * @param string $jobType
     * @param string $jobName
     */
    public function addPostProcessingJob($jobType, $jobName)
    {
        $this->postProcessingJobs[] = [
            self::JOB_TYPE_KEY => $jobType,
            self::JOB_NAME_KEY => $jobName
        ];
    }

    /**
     * @param JobExecutor $jobExecutor
     * @return PostProcessStepExecutor
     */
    public function setJobExecutor(JobExecutor $jobExecutor)
    {
        $this->jobExecutor = $jobExecutor;

        return $this;
    }

    /**
     * @param StepExecution $stepExecution
     * @return PostProcessStepExecutor
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;

        return $this;
    }

    /**
     * @param array $contextSharedKeys
     */
    public function setContextSharedKeys(array $contextSharedKeys)
    {
        $this->contextSharedKeys = $contextSharedKeys;
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        /** @var JobExecution $jobExecution */
        $jobExecution = $this->stepExecution->getJobExecution();

        return $jobExecution->getExecutionContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function write($processedItems, StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        parent::write($processedItems, $warningHandler);

        $this->runPostProcessingJobs();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        parent::execute($warningHandler);

        $requirePostprocessing = false;
        foreach ($this->contextSharedKeys as $key) {
            $sharedData = $this->getJobContext()->get($key);
            if (!empty($sharedData)) {
                $requirePostprocessing = true;
                break;
            }
        }

        if ($requirePostprocessing) {
            $this->runPostProcessingJobs();
        }
    }

    /**
     * @param string $jobType
     * @param string $jobName
     * @return JobResult
     */
    protected function executePostProcessingJob($jobType, $jobName)
    {
        $clearSkipped = $this->jobExecutor->isSkipClear();
        $this->jobExecutor->setSkipClear(true);
        $jobResult = $this->jobExecutor->executeJob($jobType, $jobName, $this->getJobConfiguration());
        $this->jobExecutor->setSkipClear($clearSkipped);

        return $jobResult;
    }

    /**
     * @return array
     */
    protected function getJobConfiguration()
    {
        $configuration = [];

        $jobContext = $this->getJobContext();
        foreach ($jobContext->getKeys() as $contextKey) {
            if (!in_array($contextKey, $this->contextSharedKeys)) {
                $configuration[$contextKey] = $jobContext->get($contextKey);
            }
        }

        foreach ($this->contextSharedKeys as $key) {
            $configuration[JobExecutor::JOB_CONTEXT_DATA_KEY][$key] = $jobContext->get($key);
        }

        return $configuration;
    }

    protected function cleanupContext()
    {
        foreach ($this->contextSharedKeys as $key) {
            $this->getJobContext()->remove($key);
        }
    }

    protected function runPostProcessingJobs()
    {
        foreach ($this->postProcessingJobs as $jobData) {
            $jobResult = $this->executePostProcessingJob($jobData[self::JOB_TYPE_KEY], $jobData[self::JOB_NAME_KEY]);
            if (!$jobResult->isSuccessful()) {
                throw new RuntimeException(
                    sprintf(
                        'Post processing job "%s" failed. Job id "%s"',
                        $jobData[self::JOB_NAME_KEY],
                        $jobResult->getJobId()
                    )
                );
            }
        }

        $this->cleanupContext();
    }
}

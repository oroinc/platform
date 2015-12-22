<?php

namespace Oro\Bundle\ImportExportBundle\Job\Step;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\BatchBundle\Step\StepExecutionWarningHandlerInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\ContextHelper;
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
     * @var ContextInterface
     */
    protected $stepExecutionContext;

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
        $this->stepExecutionContext = new StepExecutionProxyContext($this->stepExecution);

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
    public function execute(StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        $itemsToWrite = [];
        $writeCount   = 0;

        try {
            $stopExecution = false;
            while (!$stopExecution) {
                try {
                    $readItem = $this->reader->read();
                    if (null === $readItem) {
                        $stopExecution = true;
                        continue;
                    }

                } catch (InvalidItemException $e) {
                    $this->handleStepExecutionWarning($this->reader, $e, $warningHandler);

                    continue;
                }

                $processedItem = $this->process($readItem, $warningHandler);
                if (null !== $processedItem) {
                    $itemsToWrite[] = $processedItem;
                    $writeCount++;
                    if (0 === $writeCount % $this->batchSize) {
                        $this->write($itemsToWrite, $warningHandler);
                        $itemsToWrite = [];
                    }
                }

                if ($this->checkPostProcessingJobsBatch()) {
                    $this->write($itemsToWrite, $warningHandler);
                    $itemsToWrite = [];
                    $this->runPostProcessingJobs();
                }
            }

            if (count($itemsToWrite) > 0) {
                $this->write($itemsToWrite, $warningHandler);
            }

            if ($this->checkPostProcessingJobsNotEmpty()) {
                $this->runPostProcessingJobs();
            }

            $this->ensureResourcesReleased($warningHandler);
        } catch (\Exception $error) {
            $this->ensureResourcesReleased($warningHandler);
            throw $error;
        }
    }

    /**
     * @return bool
     */
    public function checkPostProcessingJobsBatch()
    {
        foreach ($this->contextSharedKeys as $key) {
            $value = $this->getJobContext()->get($key);
            if (!$value) {
                continue;
            }

            if (0 === (count($value) % $this->batchSize)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkPostProcessingJobsNotEmpty()
    {
        foreach ($this->contextSharedKeys as $key) {
            $value = $this->getJobContext()->get($key);
            if ($value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $jobType
     * @param string $jobName
     * @return JobResult
     */
    protected function executePostProcessingJob($jobType, $jobName)
    {
        $jobResult = $this->jobExecutor->executeJob($jobType, $jobName, $this->getJobConfiguration());

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
            if (!in_array($contextKey, $this->contextSharedKeys, true)) {
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
            $jobResultContext = $jobResult->getContext();
            if ($jobResultContext instanceof ContextInterface) {
                ContextHelper::mergeContextCounters($this->stepExecutionContext, $jobResultContext);
            }
            if (!$jobResult->isSuccessful()) {
                throw new RuntimeException(
                    sprintf(
                        'Post processing job "%s" failed. Job id "%s". Errors: %s',
                        $jobResult->getJobCode(),
                        $jobResult->getJobId(),
                        implode(PHP_EOL, $jobResult->getFailureExceptions())
                    )
                );
            }
        }

        $this->cleanupContext();
    }
}

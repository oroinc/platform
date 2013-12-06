<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Job;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

class Executor extends JobExecutor
{
    /**
     * @param string $jobType
     * @param string $jobName
     * @param array $configuration
     * @return JobResult
     */
    public function executeJob($jobType, $jobName, array $configuration = array())
    {
        // create and persist job instance and job execution
        $jobInstance = new JobInstance(self::CONNECTOR_NAME, $jobType, $jobName);
        $jobInstance->setCode($this->generateJobCode($jobName));
        $jobInstance->setLabel(sprintf('%s.%s', $jobType, $jobName));
        $jobInstance->setRawConfiguration($configuration);
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);

        $jobResult = new JobResult();
        $jobResult->setSuccessful(false);

        try {
            $job = $this->jobRegistry->getJob($jobInstance);
            if (!$job) {
                throw new RuntimeException(sprintf('Can\'t find job "%s"', $jobName));
            }

            $job->execute($jobExecution);

            $failureExceptions = $this->collectFailureExceptions($jobExecution);

            if ($jobExecution->getStatus()->getValue() == BatchStatus::COMPLETED && !$failureExceptions) {
                $jobResult->setSuccessful(true);
            } else {
                foreach ($failureExceptions as $failureException) {
                    $jobResult->addFailureException($failureException);
                }
            }
        } catch (\Exception $exception) {
            $jobExecution->addFailureException($exception);
            $jobResult->addFailureException($exception->getMessage());
        }

        // EntityManager can be closed when there was an exception in flush method called inside some jobs execution
        // Can't be implemented right now due to OroEntityManager external dependencies
        // on ExtendManager and FilterCollection
        if (!$this->entityManager->isOpen()) {
            $this->managerRegistry->resetManager();
            $this->entityManager = $this->managerRegistry->getManager();
        }

        // save job instance
        $this->entityManager->persist($jobInstance);
        $this->entityManager->flush($jobInstance);

        // set data to JobResult
        $jobResult->setJobId($jobInstance->getId());
        $jobResult->setJobCode($jobInstance->getCode());
        /** @var JobExecution $jobExecution */
        $jobExecution = $jobInstance->getJobExecutions()->first();
        if ($jobExecution) {
            $stepExecution = $jobExecution->getStepExecutions()->first();
            if ($stepExecution) {
                $context = $this->contextRegistry->getByStepExecution($stepExecution);
                $jobResult->setContext($context);
            }
        }

        return $jobResult;
    }
}

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
     * @param JobExecution $jobExecution
     * @param JobInstance $jobInstance
     * @return JobResult
     */
    protected function doJob(JobInstance $jobInstance, JobExecution $jobExecution)
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(false);

        try {
            $job = $this->batchJobRegistry->getJob($jobInstance);
            if (!$job) {
                throw new RuntimeException(sprintf('Can\'t find job "%s"', $jobInstance->getAlias()));
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

        return $jobResult;
    }
}

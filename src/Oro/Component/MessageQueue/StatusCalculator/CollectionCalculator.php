<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Oro\Component\MessageQueue\Job\Job;

class CollectionCalculator extends AbstractStatusCalculator
{
    /**
     * @var $rootJob Job
     */
    private $rootJob;

    /**
     * @param Job $rootJob
     */
    public function init(Job $rootJob)
    {
        $this->rootJob = $rootJob;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRootJobProgress()
    {
        $childJobs = $this->rootJob->getChildJobs();
        $numberOfChildren = count($childJobs);
        if (0 === $numberOfChildren) {
            return 0;
        }

        $processed = 0;
        foreach ($childJobs as $job) {
            if ($this->jobStatusChecker->isJobFinished($job)) {
                $processed++;
            }
        }

        return round($processed / $numberOfChildren, 4);
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->rootJob = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getChildrenInternalJobStatusCountList()
    {
        $internalJobStatusCountList = $this->getFullInternalStatusCountList([]);
        $childJobs = $this->rootJob->getChildJobs();
        foreach ($childJobs as $job) {
            $jobStatus = $job->getStatus();
            $internalStatus = $this->convertJobStatusToInternalStatus($jobStatus);
            if (false === $internalStatus) {
                throw new \LogicException(sprintf(
                    'Got unsupported job status: id: "%s" status: "%s"',
                    $job->getId(),
                    $jobStatus
                ));
            }

            $internalJobStatusCountList[$internalStatus]++;
        }

        return $internalJobStatusCountList;
    }
}

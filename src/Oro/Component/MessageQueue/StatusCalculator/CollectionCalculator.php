<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Doctrine\ORM\PersistentCollection;
use Oro\Component\MessageQueue\Job\Job;

/**
 * Calculate root job status and root job progress using jobs collection.
 */
class CollectionCalculator extends AbstractStatusCalculator
{
    /** @var Job */
    private $rootJob;

    /**
     * {@inheritdoc}
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

        if ($childJobs instanceof PersistentCollection) {
            $childJobs->setInitialized(false);
            $childJobs->initialize();
        }

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

        return $this->doJobProgressCalculation($processed, $numberOfChildren);
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

        if ($childJobs instanceof PersistentCollection) {
            $childJobs->setInitialized(false);
            $childJobs->initialize();
        }

        foreach ($childJobs as $job) {
            $jobStatus = $job->getStatus();
            $internalStatus = $this->convertJobStatusToInternalStatus($jobStatus);
            if (false === $internalStatus) {
                throw new \LogicException(
                    sprintf(
                        'Got unsupported job status: id: "%s" status: "%s"',
                        $job->getId(),
                        $jobStatus
                    )
                );
            }

            $internalJobStatusCountList[$internalStatus]++;
        }

        return $internalJobStatusCountList;
    }
}

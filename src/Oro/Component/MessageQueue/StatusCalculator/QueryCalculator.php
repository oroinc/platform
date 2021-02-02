<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

/**
 * Calculate root job status and root job progress with DB queries.
 */
class QueryCalculator extends AbstractStatusCalculator
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var Job
     */
    private $rootJob;

    /**
     * @param JobStorage $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

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
        $childJobStatusCounts = $this->jobStorage->getChildStatusesWithJobCountByRootJob($this->rootJob);

        $processed = 0;
        $childrenCount = 0;
        foreach ($childJobStatusCounts as $jobStatus => $childJobCount) {
            if ($this->jobStatusChecker->isFinishedJobStatus($jobStatus)) {
                $processed += $childJobCount;
            }

            $childrenCount += $childJobCount;
        }

        return $this->doJobProgressCalculation($processed, $childrenCount);
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
        $childJobStatusCounts = $this->jobStorage->getChildStatusesWithJobCountByRootJob($this->rootJob);

        $internalJobStatusCountList = $this->getFullInternalStatusCountList([]);
        foreach ($childJobStatusCounts as $jobStatus => $childJobCount) {
            $internalStatus = $this->convertJobStatusToInternalStatus($jobStatus);
            if (false === $internalStatus) {
                $childJobIds = $this->jobStorage->getChildJobIdsByRootJobAndStatus($this->rootJob, $jobStatus);
                throw new \LogicException(sprintf(
                    'Got unsupported job status: ids: "%s" status: "%s"',
                    implode(', ', $childJobIds),
                    $jobStatus
                ));
            }

            $internalJobStatusCountList[$internalStatus] += $childJobCount;
        }

        return $internalJobStatusCountList;
    }
}

<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

class QueryCalculator extends AbstractStatusCalculator
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var array
     */
    private $childJobStatusCounts = [];

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
        $this->childJobStatusCounts = $this->jobStorage->getChildStatusesWithJobCountByRootJob($rootJob);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRootJobProgress()
    {
        if (empty($this->childJobStatusCounts)) {
            return 0;
        }

        $processed = 0;
        $allChildCount = 0;
        foreach ($this->childJobStatusCounts as $jobStatus => $childJobCount) {
            if ($this->jobStatusChecker->isFinishedJobStatus($jobStatus)) {
                $processed += $childJobCount;
            }

            $allChildCount += $childJobCount;
        }

        return round($processed / $allChildCount, 4);
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->rootJob = null;
        $this->childJobStatusCounts = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getChildrenInternalJobStatusCountList()
    {
        $internalJobStatusCountList = $this->getFullInternalStatusCountList([]);
        foreach ($this->childJobStatusCounts as $jobStatus => $childJobCount) {
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

<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Job\Job;

abstract class AbstractStatusCalculator
{
    /**
     * @var JobStatusChecker
     */
    protected $jobStatusChecker;

    /**
     * @var array
     */
    private $baseInternalStatusCounts = [
        'new' => 0,
        'running' => 0,
        'cancelled' => 0,
        'failed' => 0,
        'success' => 0,
        'failedRedelivery' => 0
    ];

    /**
     * @var array
     */
    private $jobStatusToInternalStatusMap = [
        Job::STATUS_NEW => 'new',
        Job::STATUS_RUNNING => 'running',
        Job::STATUS_STALE => 'running',
        Job::STATUS_CANCELLED => 'cancelled',
        Job::STATUS_FAILED => 'failed',
        Job::STATUS_FAILED_REDELIVERED => 'failedRedelivery',
        Job::STATUS_SUCCESS => 'success'
    ];

    /**
     * @param JobStatusChecker $jobStatusChecker
     */
    public function setJobStatusChecker(JobStatusChecker $jobStatusChecker)
    {
        $this->jobStatusChecker = $jobStatusChecker;
    }

    /**
     * @param Job $rootJob
     */
    abstract public function init(Job $rootJob);

    /**
     * @return float
     */
    abstract public function calculateRootJobProgress();

    /**
     * @return void
     */
    abstract public function clean();

    /**
     * @return string
     */
    public function calculateRootJobStatus()
    {
        $childrenInternalJobStatusCounts = $this->getChildrenInternalJobStatusCountList();
        return $this->getRootJobStatus($childrenInternalJobStatusCounts);
    }

    /**
     * @return array
     */
    abstract protected function getChildrenInternalJobStatusCountList();

    /**
     * @param $status
     *
     * @return false|string, string on success or false in case if unsupported type given
     */
    protected function convertJobStatusToInternalStatus($status)
    {
        if (false === array_key_exists($status, $this->jobStatusToInternalStatusMap)) {
            return false;
        }

        return $this->jobStatusToInternalStatusMap[$status];
    }

    /**
     * @param array $childrenInternalJobStatusCounts
     *
     * @return array
     */
    protected function getFullInternalStatusCountList(array $childrenInternalJobStatusCounts)
    {
        return array_merge($this->baseInternalStatusCounts, $childrenInternalJobStatusCounts);
    }

    /**
     * @param int $processedChildrenCount
     * @param int $childrenCount
     *
     * @return float
     */
    protected function doJobProgressCalculation($processedChildrenCount, $childrenCount)
    {
        return round($processedChildrenCount / $childrenCount, 4);
    }

    /**
     * @param array $childrenInternalJobStatusCounts
     *
     * @return string
     */
    private function getRootJobStatus(array $childrenInternalJobStatusCounts)
    {
        $statusCountList = $this->getFullInternalStatusCountList($childrenInternalJobStatusCounts);

        $status = Job::STATUS_NEW;
        if (!$statusCountList['new'] && !$statusCountList['running'] && !$statusCountList['failedRedelivery']) {
            if ($statusCountList['cancelled']) {
                $status = Job::STATUS_CANCELLED;
            } elseif ($statusCountList['failed']) {
                $status = Job::STATUS_FAILED;
            } else {
                $status = Job::STATUS_SUCCESS;
            }
        } elseif ($statusCountList['running'] ||
            $statusCountList['cancelled'] ||
            $statusCountList['failed'] ||
            $statusCountList['success'] ||
            $statusCountList['failedRedelivery']
        ) {
            $status = Job::STATUS_RUNNING;
        }

        return $status;
    }
}

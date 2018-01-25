<?php

namespace Oro\Component\MessageQueue\Checker;

use Oro\Component\MessageQueue\Job\Job;

class JobStatusChecker
{
    /** @var string[] */
    private static $finishStatuses = [
        Job::STATUS_SUCCESS,
        Job::STATUS_FAILED
    ];

    /** @var string[] */
    private static $stopStatuses = [
        Job::STATUS_SUCCESS,
        Job::STATUS_FAILED,
        Job::STATUS_CANCELLED,
        Job::STATUS_STALE
    ];

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isJobStopped(Job $job)
    {
        return $this->isStoppedJobStatus($job->getStatus());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isJobFinished(Job $job)
    {
        return $this->isFinishedJobStatus($job->getStatus());
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    public function isStoppedJobStatus($status)
    {
        return in_array($status, self::$stopStatuses, true);
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    public function isFinishedJobStatus($status)
    {
        return in_array($status, self::$finishStatuses, true);
    }
}

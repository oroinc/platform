<?php

namespace Oro\Component\MessageQueue\Checker;

use Oro\Component\MessageQueue\Job\Job;

/**
 * Checks the status of asynchronous jobs to determine if they have finished or stopped.
 *
 * This utility class provides methods to query job status against predefined sets of
 * finished and stopped statuses. It helps determine whether a job has completed execution
 * (successfully or with failure) or has been stopped (including cancellation and stale states),
 * enabling proper job lifecycle management and monitoring.
 */
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

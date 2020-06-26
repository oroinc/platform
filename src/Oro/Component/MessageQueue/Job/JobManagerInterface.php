<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Jobs insert/update database layer interface.
 */
interface JobManagerInterface
{
    /**
     * @param Job $job
     * @param \Closure $lockCallback
     */
    public function saveJobWithLock(Job $job, \Closure $lockCallback): void;

    /**
     * @param Job $job
     */
    public function saveJob(Job $job): void;

    /**
     * @param Job $rootJob
     * @param array $statuses
     * @param \DateTime $stoppedAt
     * @param \DateTime|null $startedAt
     */
    public function setCancelledStatusForChildJobs(
        Job $rootJob,
        array $statuses,
        \DateTime $stoppedAt,
        \DateTime $startedAt = null
    ): void;
}

<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Jobs insert/update database layer interface.
 */
interface JobManagerInterface
{
    public function saveJobWithLock(Job $job, \Closure $lockCallback): void;

    public function saveJob(Job $job): void;

    public function setCancelledStatusForChildJobs(
        Job $rootJob,
        array $statuses,
        \DateTime $stoppedAt,
        \DateTime $startedAt = null
    ): void;
}

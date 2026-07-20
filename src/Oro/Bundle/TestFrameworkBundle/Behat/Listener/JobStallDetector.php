<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Oro\Bundle\MessageQueueBundle\Entity\Job;

/**
 * Decides, based on the active jobs observed over successive polls, whether the consumers appear stuck.
 *
 * A job that keeps the RUNNING status is usually a legitimate long-running job (e.g. a full search reindex)
 * and is therefore given a much larger grace period before it is considered stuck. Jobs that stay in
 * NEW / FAILED_REDELIVERED mean nothing is picking them up (a dead/hung consumer) and are recovered on the
 * base threshold. Any change to the set of jobs or their statuses counts as progress and resets the timer.
 *
 * A threshold of zero (or less) disables stall detection entirely.
 */
class JobStallDetector
{
    private const RUNNING_JOB_STUCK_MULTIPLIER = 10;

    private array $lastProgressSignature = [];
    private float $lastProgressAt;

    public function __construct(private int $stuckThresholdSeconds)
    {
        $this->lastProgressAt = microtime(true);
    }

    /**
     * @param array<array{id: int|string, name: string, status: string}> $activeJobs
     * @param float|null $now Current timestamp; injectable for deterministic tests.
     */
    public function isStuck(array $activeJobs, ?float $now = null): bool
    {
        if ($this->stuckThresholdSeconds <= 0) {
            return false;
        }

        $now ??= microtime(true);

        $signature = array_map(static fn ($job) => $job['id'] . ':' . $job['status'], $activeJobs);
        sort($signature);

        if ($signature !== $this->lastProgressSignature) {
            $this->lastProgressSignature = $signature;
            $this->lastProgressAt = $now;

            return false;
        }

        $threshold = $this->hasRunningJob($activeJobs)
            ? $this->stuckThresholdSeconds * self::RUNNING_JOB_STUCK_MULTIPLIER
            : $this->stuckThresholdSeconds;

        return ($now - $this->lastProgressAt) >= $threshold;
    }

    /**
     * Clears the observed state so the next poll re-establishes a fresh baseline
     * (e.g. after consumers have been restarted).
     */
    public function reset(): void
    {
        $this->lastProgressSignature = [];
        $this->lastProgressAt = microtime(true);
    }

    private function hasRunningJob(array $activeJobs): bool
    {
        foreach ($activeJobs as $job) {
            if ($job['status'] === Job::STATUS_RUNNING) {
                return true;
            }
        }

        return false;
    }
}

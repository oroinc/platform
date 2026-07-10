<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Listener;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\JobStallDetector;
use PHPUnit\Framework\TestCase;

class JobStallDetectorTest extends TestCase
{
    private const THRESHOLD = 30;
    private const RUNNING_MULTIPLIER = 10;

    private function job(int $id, string $status): array
    {
        return ['id' => $id, 'name' => 'oro.test.job.' . $id, 'status' => $status];
    }

    public function testIsNotStuckOnFirstObservation(): void
    {
        $detector = new JobStallDetector(self::THRESHOLD);

        self::assertFalse($detector->isStuck([$this->job(1, Job::STATUS_NEW)], 1000.0));
    }

    public function testChangingJobSetResetsTheStallTimer(): void
    {
        $detector = new JobStallDetector(self::THRESHOLD);

        $detector->isStuck([$this->job(1, Job::STATUS_NEW)], 1000.0);
        // A different set of jobs is progress: even long after, it is not yet stuck.
        self::assertFalse($detector->isStuck([$this->job(2, Job::STATUS_NEW)], 1000.0 + 100));
        // ...and the timer restarted from the second observation.
        self::assertFalse($detector->isStuck([$this->job(2, Job::STATUS_NEW)], 1000.0 + 100 + self::THRESHOLD - 1));
    }

    public function testStatusTransitionCountsAsProgress(): void
    {
        $detector = new JobStallDetector(self::THRESHOLD);

        $detector->isStuck([$this->job(1, Job::STATUS_NEW)], 1000.0);
        // Same job id, but NEW -> RUNNING is real progress and must reset the timer.
        self::assertFalse($detector->isStuck([$this->job(1, Job::STATUS_RUNNING)], 1000.0 + 100));
    }

    public function testStuckNewJobsAreDetectedAfterThreshold(): void
    {
        $detector = new JobStallDetector(self::THRESHOLD);
        $jobs = [$this->job(1, Job::STATUS_NEW)];

        $detector->isStuck($jobs, 1000.0);
        // Just under the base threshold: not stuck yet.
        self::assertFalse($detector->isStuck($jobs, 1000.0 + self::THRESHOLD - 1));
        // At/after the base threshold with nothing picking the NEW job up: stuck.
        self::assertTrue($detector->isStuck($jobs, 1000.0 + self::THRESHOLD));
    }

    public function testRunningJobIsGivenALargerGracePeriod(): void
    {
        $detector = new JobStallDetector(self::THRESHOLD);
        $jobs = [$this->job(1, Job::STATUS_RUNNING)];
        $runningThreshold = self::THRESHOLD * self::RUNNING_MULTIPLIER;

        $detector->isStuck($jobs, 1000.0);
        // Past the base threshold but within the running-job grace period: must NOT be stuck.
        self::assertFalse($detector->isStuck($jobs, 1000.0 + self::THRESHOLD + 1));
        // Past the running-job grace period: now stuck.
        self::assertTrue($detector->isStuck($jobs, 1000.0 + $runningThreshold));
    }

    public function testResetClearsTheStallState(): void
    {
        $detector = new JobStallDetector(self::THRESHOLD);
        $jobs = [$this->job(1, Job::STATUS_NEW)];

        $detector->isStuck($jobs, 1000.0);
        self::assertTrue($detector->isStuck($jobs, 1000.0 + self::THRESHOLD));

        // Consumers were restarted: clear the state so the same jobs get a fresh window.
        $detector->reset();
        self::assertFalse($detector->isStuck($jobs, 1000.0 + self::THRESHOLD));
        // Stuck again only after a fresh full threshold measured from the post-reset baseline.
        self::assertTrue($detector->isStuck($jobs, 1000.0 + 2 * self::THRESHOLD));
    }

    /**
     * @dataProvider disabledThresholdDataProvider
     */
    public function testStallDetectionIsDisabledForNonPositiveThreshold(int $threshold): void
    {
        $detector = new JobStallDetector($threshold);
        $jobs = [$this->job(1, Job::STATUS_NEW)];

        $detector->isStuck($jobs, 1000.0);

        self::assertFalse($detector->isStuck($jobs, 1000.0 + 100000));
    }

    public function disabledThresholdDataProvider(): array
    {
        return [
            'zero' => ['threshold' => 0],
            'negative' => ['threshold' => -1],
        ];
    }
}

<?php

namespace Oro\Component\MessageQueue\Tests\Unit\StatusCalculator;

use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\StatusCalculator\CollectionCalculator;

class CollectionCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectionCalculator */
    private $collectionCalculator;

    protected function setUp(): void
    {
        $this->collectionCalculator = new CollectionCalculator();
        $this->collectionCalculator->setJobStatusChecker(new JobStatusChecker());
    }

    public function calculateProgressProvider(): array
    {
        return [
            [[Job::STATUS_NEW, Job::STATUS_NEW], 0],
            [[Job::STATUS_RUNNING, Job::STATUS_NEW], 0],
            [[Job::STATUS_SUCCESS, Job::STATUS_NEW], 0.5],
            [[Job::STATUS_SUCCESS, Job::STATUS_RUNNING, Job::STATUS_NEW], 0.3333],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_RUNNING], 0.6667],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_SUCCESS], 1],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED], 0.6667],
            [[Job::STATUS_SUCCESS, Job::STATUS_STALE, Job::STATUS_STALE], 0.3333],
        ];
    }

    /**
     * @dataProvider calculateProgressProvider
     */
    public function testCalculateRootJobProgress(array $statuses, float|int $expectedStatusProgress)
    {
        $rootJob = $this->getRootJobFilledWithChildJobWithGivenStatuses($statuses);

        $this->collectionCalculator->init($rootJob);
        $statusProgress = $this->collectionCalculator->calculateRootJobProgress();
        $this->assertEquals($expectedStatusProgress, $statusProgress);
    }

    public function statusCalculateProvider(): array
    {
        return [
            [[Job::STATUS_NEW, Job::STATUS_NEW], Job::STATUS_NEW],
            [[Job::STATUS_RUNNING, Job::STATUS_NEW], Job::STATUS_RUNNING],
            [[Job::STATUS_SUCCESS, Job::STATUS_NEW], Job::STATUS_RUNNING],
            [[Job::STATUS_SUCCESS, Job::STATUS_RUNNING, Job::STATUS_NEW], Job::STATUS_RUNNING],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_RUNNING], Job::STATUS_RUNNING],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_SUCCESS], Job::STATUS_FAILED],
            [[Job::STATUS_SUCCESS, Job::STATUS_SUCCESS, Job::STATUS_SUCCESS], Job::STATUS_SUCCESS],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED], Job::STATUS_CANCELLED],
        ];
    }

    /**
     * @dataProvider statusCalculateProvider
     */
    public function testCalculateRootJobStatus(array $statuses, string $expectedStatus)
    {
        $rootJob = $this->getRootJobFilledWithChildJobWithGivenStatuses($statuses);

        $this->collectionCalculator->init($rootJob);
        $status = $this->collectionCalculator->calculateRootJobStatus();
        $this->assertEquals($expectedStatus, $status);
    }

    private function getRootJobFilledWithChildJobWithGivenStatuses(array $statuses): Job
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        foreach ($statuses as $status) {
            $childJob = new Job();
            $childJob->setRootJob($rootJob);
            $childJob->setStatus($status);
            $rootJob->addChildJob($childJob);
        }

        return $rootJob;
    }
}

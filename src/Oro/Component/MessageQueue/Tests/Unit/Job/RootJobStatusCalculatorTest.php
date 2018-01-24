<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator;
use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\StatusCalculator\CollectionCalculator;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RootJobStatusCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|JobStorage */
    private $jobStorage;

    /** @var RootJobStatusCalculator */
    private $rootJobStatusCalculator;

    protected function setUp()
    {
        $this->jobStorage = $this->createMock(JobStorage::class);

        $jobStatusChecker = new JobStatusChecker();
        $statusCalculator = new CollectionCalculator();
        $statusCalculator->setJobStatusChecker($jobStatusChecker);

        $statusCalculatorResolver = $this->createMock(StatusCalculatorResolver::class);
        $statusCalculatorResolver
            ->method('getCalculatorForRootJob')
            ->willReturnCallback(
                function (Job $rootJob) use ($statusCalculator) {
                    $statusCalculator->init($rootJob);
                    return $statusCalculator;
                }
            );


        $this->rootJobStatusCalculator = new RootJobStatusCalculator(
            $this->jobStorage,
            $jobStatusChecker,
            $statusCalculatorResolver
        );
    }

    public function stopStatusProvider()
    {
        return [
            [Job::STATUS_SUCCESS],
            [Job::STATUS_FAILED],
            [Job::STATUS_CANCELLED],
        ];
    }

    /**
     * @dataProvider stopStatusProvider
     */
    public function testShouldDoNothingIfRootJobHasStopState($status)
    {
        $rootJob = new Job();
        $rootJob->setStatus($status);

        $notRootJob = new Job();
        $notRootJob->setRootJob($rootJob);

        $this->jobStorage->expects(self::never())
            ->method('saveJob');

        $this->rootJobStatusCalculator->calculate($notRootJob);
    }

    public function testShouldCalculateRootJobStatus()
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_RUNNING);

        $rootJob->setChildJobs([$childJob]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($childJob);

        self::assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
        self::assertNull($rootJob->getStoppedAt());
    }

    /**
     * @dataProvider stopStatusProvider
     */
    public function testShouldCalculateRootJobStatusAndSetStoppedAtTimeIfGotStopStatus($stopStatus)
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus($stopStatus);

        $rootJob->setChildJobs([$childJob]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($childJob);

        self::assertEquals($stopStatus, $rootJob->getStatus());
        self::assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
    }

    public function testShouldSetStoppedAtOnlyIfWasNotSet()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setStoppedAt(new \DateTime('2012-12-12 12:12:12'));

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($childJob);

        self::assertEquals(new \DateTime('2012-12-12 12:12:12'), $rootJob->getStoppedAt());
    }

    public function testShouldThrowIfInvalidStatus()
    {
        $rootJob = new Job();

        $childJob = new Job();
        $childJob->setId(12345);
        $childJob->setRootJob($rootJob);
        $childJob->setStatus('invalid-status');

        $rootJob->setChildJobs([$childJob]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Got unsupported job status: id: "12345" status: "invalid-status"');

        $this->rootJobStatusCalculator->calculate($childJob);
    }

    public function testShouldSetStatusNewIfAllChildJobsAreNew()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_NEW);

        $rootJob->setChildJobs([$childJob1, $childJob2]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_NEW, $rootJob->getStatus());
    }

    public function testShouldSetStatusRunningIfAnyOneIsRunning()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_RUNNING);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusRunningIfThereIsNoRunningButNewAndAnyOfStopStatus()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_CANCELLED);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusCancelledIfAllIsStopButOneIsCancelled()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_CANCELLED);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_CANCELLED, $rootJob->getStatus());
    }

    public function testShouldSetStatusFailedIfThereIsAnyOneIsFailedButIsNotCancelled()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_FAILED, $rootJob->getStatus());
    }

    public function testShouldSetStatusFailedRedeliveredIfThereIsAnyOneIsFailedRedelivered()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED_REDELIVERED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusSuccessIfAllAreSuccess()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_SUCCESS, $rootJob->getStatus());
    }

    public function calculateProgressProvider()
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
    public function testShouldCalculateRootJobProgress($statuses, $expectedProgress)
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        foreach ($statuses as $status) {
            $childJob = new Job();
            $childJob->setRootJob($rootJob);
            $childJob->setStatus($status);
            $rootJob->addChildJob($childJob);
        }

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob, true);
        self::assertEquals($expectedProgress, $rootJob->getJobProgress());
    }

    public function testShouldCalculateRootJobProgressIfRootJobIsStopped()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2]);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_SUCCESS, $rootJob->getStatus());
        self::assertEquals(1, $rootJob->getJobProgress());
    }
}

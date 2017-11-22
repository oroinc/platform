<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator;

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

        $this->rootJobStatusCalculator = new RootJobStatusCalculator($this->jobStorage);
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
            [[Job::STATUS_SUCCESS, Job::STATUS_STALE, Job::STATUS_STALE], 1],
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

    public function testShouldNotLoadChildJobsFromDatabaseIfPersistentCollectionIsInitialized()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setStatus(Job::STATUS_SUCCESS);
        $childJob2 = new Job();
        $childJob2->setStatus(Job::STATUS_RUNNING);

        $childJobCollection = new PersistentCollection(
            $this->createMock(EntityManager::class),
            Job::class,
            new ArrayCollection([$childJob1, $childJob2])
        );
        $rootJob->setChildJobs($childJobCollection);

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldLoadChildJobsFromDatabaseUsingArrayHydratorIfPersistentCollectionIsNotInitialized()
    {
        $rootJob = new Job();

        $childJobCollection = new PersistentCollection(
            $this->createMock(EntityManager::class),
            Job::class,
            new ArrayCollection()
        );
        $childJobCollection->setInitialized(false);
        $rootJob->setChildJobs($childJobCollection);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->jobStorage->expects(self::once())
            ->method('createJobQueryBuilder')
            ->with('e')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('select')
            ->with('e.id, e.status')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('e.rootJob = :rootJob')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('rootJob', self::isInstanceOf($rootJob))
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 10, 'status' => Job::STATUS_SUCCESS],
                ['id' => 20, 'status' => Job::STATUS_RUNNING],
            ]);
        $this->jobStorage->expects(self::exactly(2))
            ->method('createJob')
            ->willReturn(new Job());

        $this->jobStorage->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            });

        $this->rootJobStatusCalculator->calculate($rootJob);

        self::assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }
}

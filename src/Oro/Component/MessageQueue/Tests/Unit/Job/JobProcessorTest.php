<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class JobProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $jobStorage;

    /** @var JobManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobManager;

    /** @var JobProcessor */
    private $jobProcessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->jobStorage = $this->createMock(JobStorage::class);
        $this->jobManager = $this->createMock(JobManagerInterface::class);

        $this->jobProcessor = new JobProcessor($this->jobStorage);
        $this->jobProcessor->setJobManager($this->jobManager);
    }

    public function testCreateRootJobShouldThrowIfOwnerIdIsEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('OwnerId must not be empty');

        $this->jobProcessor->findOrCreateRootJob(null, 'job-name', true);
    }

    public function testCreateRootJobShouldThrowIfNameIsEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job name must not be empty');

        $this->jobProcessor->findOrCreateRootJob('owner-id', null, true);
    }

    public function testShouldCreateRootJobAndReturnIt()
    {
        $job = new Job();

        $this->jobStorage->expects(self::once())
            ->method('createJob')
            ->willReturn($job);
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($job));
        $this->jobStorage->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name');

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertSame($job, $result);
        self::assertEquals(Job::STATUS_NEW, $job->getStatus());
        self::assertLessThanOrEqual(new \DateTime(), $job->getCreatedAt());
        self::assertLessThanOrEqual(new \DateTime(), $job->getStartedAt());
        self::assertNull($job->getStoppedAt());
        self::assertEquals('job-name', $job->getName());
        self::assertEquals('owner-id', $job->getOwnerId());
    }

    public function testShouldCatchDuplicateJobAndReturnNull()
    {
        $job = new Job();

        $this->jobStorage->expects(self::once())
            ->method('createJob')
            ->willReturn($job);
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($job))
            ->willThrowException(new DuplicateJobException());
        $this->jobStorage->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name');

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testFindOrCreateRootJobFindJobAndReturn()
    {
        $job = new Job();

        $this->jobStorage->expects(self::never())
            ->method('createJob');
        $this->jobManager->expects(self::never())
            ->method('saveJob');
        $this->jobStorage->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name')
            ->willReturn($job);

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertSame($job, $result);
    }

    public function testShouldCatchDuplicateCheckIfItIsStaleAndChangeStatus()
    {
        $job = new Job();
        $job->setChildJobs([]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job, 0, $job);
        $this->jobManager
            ->expects($this->exactly(2))
            ->method('saveJob')
            ->withConsecutive(
                [$job],
                [$job]
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DuplicateJobException())
            );

        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertSame($job, $result);
    }

    public function testFindOrCreateReturnsNullIfRootJobInActiveStatusCannotBeFound()
    {
        $job = new Job();
        $job->setChildJobs([]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager
            ->expects($this->once())
            ->method('saveJob')
            ->with($job)
            ->willThrowException(new DuplicateJobException());

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testFindOrCreateReturnsNullIfRootJobIsNotStaleYet()
    {
        $job = new Job();
        $job->setChildJobs([]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job, 100, $job);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager
            ->expects($this->once())
            ->method('saveJob')
            ->with($job)
            ->willThrowException(new DuplicateJobException());

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testFindOrCreateReturnsNullIfRootJobStaleByTimeButHaveNotStartedChild()
    {
        $job = new Job();
        $childJob = new Job();
        $childJob->setStatus(Job::STATUS_NEW);
        $job->setChildJobs([$childJob]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job, 0, $job);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager
            ->expects($this->once())
            ->method('saveJob')
            ->with($job)
            ->willThrowException(new DuplicateJobException());

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testStaleRootJobAndChildrenWillChangeStatusForRootAndRunningChildren()
    {
        $rootJob = new Job();
        $rootJob->setId(1);
        $childJob1 = new Job();
        $childJob1->setId(11);
        $childJob1->setStatus(Job::STATUS_RUNNING);
        $childJob1->setRootJob($rootJob);
        $childJob2 = new Job();
        $childJob2->setId(12);
        $childJob2->setStatus(Job::STATUS_SUCCESS);
        $childJob2->setRootJob($rootJob);
        $rootJob->addChildJob($childJob1);
        $rootJob->addChildJob($childJob2);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($rootJob, 0, $rootJob);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager
            ->expects($this->exactly(3))
            ->method('saveJob')
            ->withConsecutive(
                [$rootJob],
                [$childJob1],
                [$rootJob]
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DuplicateJobException())
            );

        $this->jobManager
            ->expects($this->once())
            ->method('saveJobWithLock')
            ->with($rootJob)
            ->willReturnCallback(static function (Job $job, $callback) {
                $callback($job);
            });

        $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertSame(Job::STATUS_STALE, $rootJob->getStatus());
        self::assertSame(Job::STATUS_STALE, $childJob1->getStatus());
        self::assertSame(Job::STATUS_SUCCESS, $childJob2->getStatus());
    }

    public function testCreateChildJobShouldThrowIfNameIsEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job name must not be empty');

        $this->jobProcessor->findOrCreateChildJob(null, new Job());
    }

    public function testCreateChildJobShouldFindAndReturnAlreadyCreatedJob()
    {
        $job = new Job();
        $job->setId(123);

        $this->jobStorage->expects(self::never())
            ->method('createJob');
        $this->jobManager->expects(self::never())
            ->method('saveJob');
        $this->jobStorage->expects(self::once())
            ->method('findChildJobByName')
            ->with('job-name', self::identicalTo($job))
            ->willReturn($job);

        $result = $this->jobProcessor->findOrCreateChildJob('job-name', $job);

        self::assertSame($job, $result);
    }

    public function testCreateChildJobShouldCreateAndSaveJobAndPublishRecalculateRootMessage()
    {
        $job = new Job();
        $job->setId(12345);

        $this->jobStorage->expects(self::once())
            ->method('createJob')
            ->willReturn($job);
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($job));
        $this->jobStorage->expects(self::once())
            ->method('findChildJobByName')
            ->with('job-name', self::identicalTo($job))
            ->willReturn(null);

        $result = $this->jobProcessor->findOrCreateChildJob('job-name', $job);

        self::assertSame($job, $result);
        self::assertEquals(Job::STATUS_NEW, $job->getStatus());
        self::assertLessThanOrEqual(new \DateTime(), $job->getCreatedAt());
        self::assertNull($job->getStartedAt());
        self::assertNull($job->getStoppedAt());
        self::assertEquals('job-name', $job->getName());
        self::assertNull($job->getOwnerId());
    }

    public function testStartChildJobShouldThrowIfRootJob()
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t start root jobs. id: "12345"');

        $this->jobProcessor->startChildJob($rootJob);
    }

    public function testStartChildJobShouldThrowIfJobHasNotNewStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can start only new jobs: id: "12345", status: "oro.message_queue_job.status.cancelled"'
        );

        $this->jobProcessor->startChildJob($job);
    }

    /**
     * @return array
     */
    public function getStatusThatCanRun()
    {
        return [
            [Job::STATUS_NEW],
            [Job::STATUS_FAILED_REDELIVERED],
        ];
    }

    /**
     * @param string $jobStatus
     * @dataProvider getStatusThatCanRun
     */
    public function testStartJobShouldUpdateJobWithRunningStatusAndStartAtTime($jobStatus)
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus($jobStatus);

        $this->jobManager->expects(self::any())
            ->method('saveJob')
            ->with(self::isInstanceOf(Job::class));

        $this->jobProcessor->startChildJob($job);

        self::assertEquals(Job::STATUS_RUNNING, $job->getStatus());
        self::assertLessThanOrEqual(new \DateTime(), $job->getStartedAt());
    }

    public function testSuccessChildJobShouldThrowIfRootJob()
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t success root jobs. id: "12345"');

        $this->jobProcessor->successChildJob($rootJob);
    }

    public function testSuccessJobShouldUpdateJobWithSuccessStatusAndStopAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $this->jobManager->expects(self::any())
            ->method('saveJob')
            ->with(self::isInstanceOf(Job::class));

        $this->jobProcessor->successChildJob($job);

        self::assertEquals(Job::STATUS_SUCCESS, $job->getStatus());
        self::assertLessThanOrEqual(new \DateTime(), $job->getStoppedAt());
    }

    public function testFailChildJobShouldThrowIfRootJob()
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t fail root jobs. id: "12345"');

        $this->jobProcessor->failChildJob($rootJob);
    }

    public function testFailJobShouldUpdateJobWithFailStatusAndStopAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $this->jobManager->expects($this->once())
            ->method('saveJob')
            ->with(self::isInstanceOf(Job::class));

        $this->jobProcessor->failChildJob($job);

        self::assertEquals(Job::STATUS_FAILED, $job->getStatus());
        $stoppedAt = $job->getStoppedAt();
        self::assertInstanceOf(\DateTime::class, $stoppedAt);
        self::assertLessThanOrEqual(new \DateTime(), $stoppedAt);
    }

    public function testInterruptRootJobLogicException(): void
    {
        $notRootJob = new Job();
        $notRootJob->setId(123);
        $notRootJob->setRootJob(new Job());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can interrupt only root jobs. id: "123"');

        $this->jobProcessor->interruptRootJob($notRootJob);
    }

    public function testInterruptRootJobAlreadyInterrupted(): void
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setInterrupted(true);

        $this->jobManager->expects(self::never())
            ->method('saveJob');

        $this->jobProcessor->interruptRootJob($rootJob);
    }

    public function testInterruptRootJob(): void
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $childRunnedJob = new Job();
        $childRunnedJob->setId(1234);
        $childRunnedJob->setStatus(Job::STATUS_RUNNING);
        $childRunnedJob->setRootJob($rootJob);

        $childNewJob = new Job();
        $childNewJob->setId(1235);
        $childNewJob->setStatus(Job::STATUS_NEW);
        $childNewJob->setRootJob($rootJob);

        $childRedeliveredJob = new Job();
        $childRedeliveredJob->setId(1236);
        $childRedeliveredJob->setStatus(Job::STATUS_FAILED_REDELIVERED);
        $childRedeliveredJob->setRootJob($rootJob);

        $rootJob->setChildJobs([$childRunnedJob, $childNewJob, $childRedeliveredJob]);

        $this->jobManager
            ->expects(self::once())
            ->method('saveJobWithLock')
            ->willReturnCallback(static function (Job $job, $callback) {
                $callback($job);
            });

        $this->jobManager
            ->expects(self::exactly(2))
            ->method('setCancelledStatusForChildJobs')
            ->withConsecutive(
                [$rootJob, [Job::STATUS_NEW]],
                [$rootJob, [Job::STATUS_FAILED_REDELIVERED]]
            );

        $this->jobProcessor->interruptRootJob($rootJob);

        self::assertTrue($rootJob->isInterrupted());
        self::assertNotNull($rootJob->getStoppedAt());
    }

    public function testFailAndRedeliveryChildJob()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $this->jobProcessor->failAndRedeliveryChildJob($job);

        self::assertEquals(Job::STATUS_FAILED_REDELIVERED, $job->getStatus());
    }

    /**
     * @param Job      $job
     * @param int      $timeForStale
     * @param Job|null $rootJobFoundByStorage
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|JobConfigurationProviderInterface
     */
    private function configureBaseMocksForStaleJobsCases(
        Job $job,
        int $timeForStale = 0,
        $rootJobFoundByStorage = null
    ) {
        $jobConfigurationProvider = $this->createMock(JobConfigurationProviderInterface::class);
        $jobConfigurationProvider->expects(self::any())
            ->method('getTimeBeforeStaleForJobName')
            ->willReturn($timeForStale);

        $this->jobStorage->expects(self::once())
            ->method('createJob')
            ->willReturn($job);

        $this->jobStorage->expects(self::once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn($rootJobFoundByStorage);

        $this->jobStorage->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName');

        return $jobConfigurationProvider;
    }

    /**
     * @param Job|null $job
     * @param bool $expectedResult
     * @dataProvider getIsRootJobExistsAndNotStaleProvider
     */
    public function testIsRootJobExistsAndNotStale($job, $expectedResult)
    {
        $this->jobStorage
            ->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->with('job-name', [])
            ->willReturn($job);

        static::assertEquals(
            $expectedResult,
            $this->jobProcessor->findNotStaleRootJobyJobNameAndStatuses('job-name', [])
        );
    }

    /**
     * @return array
     */
    public function getIsRootJobExistsAndNotStaleProvider()
    {
        $rootJob = new Job();
        $rootJob->setId(1);
        $rootJob->setChildJobs([]);

        return [
            'job not found' => [
                'job' => null,
                'expectedResult' => null
            ],
            'job not stale' => [
                'job' => $rootJob,
                'expectedResult' => $rootJob
            ]
        ];
    }

    public function testIsRootJobExistsAndNotStaleIfJobStale()
    {
        $rootJob = new Job();
        $rootJob->setId(1);
        $rootJob->setChildJobs([]);

        $this->jobStorage
            ->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn($rootJob);
        $this->jobManager
            ->expects($this->once())
            ->method('saveJobWithLock')
            ->with($rootJob);

        /** @var JobConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject $jobConfigurationProvider */
        $jobConfigurationProvider = $this->createMock(JobConfigurationProviderInterface::class);
        $jobConfigurationProvider
            ->expects($this->any())
            ->method('getTimeBeforeStaleForJobName')
            ->will($this->returnValue(0));

        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);

        static::assertNull($this->jobProcessor->findNotStaleRootJobyJobNameAndStatuses('job-name', []));
    }
}

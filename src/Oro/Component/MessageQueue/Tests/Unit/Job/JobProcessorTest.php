<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Exception\JobCannotBeStartedException;
use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRepositoryInterface;
use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class JobProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var JobManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobManager;

    /** @var JobProcessor */
    private $jobProcessor;

    protected function setUp(): void
    {
        $this->jobManager = $this->createMock(JobManagerInterface::class);
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);
        $entityClass = Job::class;
        $manager = $this->createMock(ManagerRegistry::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($this->jobRepository);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);

        $this->jobProcessor = new JobProcessor($this->jobManager, $doctrine, $entityClass);
    }

    public function testCreateRootJobShouldThrowIfOwnerIdIsEmpty(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('OwnerId must not be empty');

        $this->jobProcessor->findOrCreateRootJob('', 'job-name', true);
    }

    public function testCreateRootJobShouldThrowIfNameIsEmpty(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job name must not be empty');

        $this->jobProcessor->findOrCreateRootJob('owner-id', '', true);
    }

    public function testShouldCreateRootJobAndReturnIt(): void
    {
        $job = new Job();

        $this->jobRepository->expects(self::once())
            ->method('createJob')
            ->willReturn($job);
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($job));
        $this->jobRepository->expects(self::once())
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

    public function testShouldCatchDuplicateJobAndReturnNull(): void
    {
        $job = new Job();

        $this->jobRepository->expects(self::once())
            ->method('createJob')
            ->willReturn($job);
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($job))
            ->willThrowException(new DuplicateJobException());
        $this->jobRepository->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name');

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testFindOrCreateRootJobFindJobAndReturn(): void
    {
        $job = new Job();

        $this->jobRepository->expects(self::never())
            ->method('createJob');
        $this->jobManager->expects(self::never())
            ->method('saveJob');
        $this->jobRepository->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name')
            ->willReturn($job);

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertSame($job, $result);
    }

    public function testShouldCatchDuplicateCheckIfItIsStaleAndChangeStatus(): void
    {
        $job = new Job();
        $job->setChildJobs([]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job, 0, $job);
        $this->jobManager->expects($this->exactly(2))
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

    public function testFindOrCreateReturnsNullIfRootJobInActiveStatusCannotBeFound(): void
    {
        $job = new Job();
        $job->setChildJobs([]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager->expects($this->once())
            ->method('saveJob')
            ->with($job)
            ->willThrowException(new DuplicateJobException());

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testFindOrCreateReturnsNullIfRootJobIsNotStaleYet(): void
    {
        $job = new Job();
        $job->setChildJobs([]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job, 100, $job);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager->expects($this->once())
            ->method('saveJob')
            ->with($job)
            ->willThrowException(new DuplicateJobException());

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testFindOrCreateReturnsNullIfRootJobStaleByTimeButHaveNotStartedChild(): void
    {
        $job = new Job();
        $childJob = new Job();
        $childJob->setStatus(Job::STATUS_NEW);
        $job->setChildJobs([$childJob]);

        $jobConfigurationProvider = $this->configureBaseMocksForStaleJobsCases($job, 0, $job);
        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);
        $this->jobManager->expects($this->once())
            ->method('saveJob')
            ->with($job)
            ->willThrowException(new DuplicateJobException());

        $result = $this->jobProcessor->findOrCreateRootJob('owner-id', 'job-name', true);

        self::assertNull($result);
    }

    public function testStaleRootJobAndChildrenWillChangeStatusForRootAndRunningChildren(): void
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
        $this->jobManager->expects($this->exactly(3))
            ->method('saveJob')
            ->withConsecutive(
                [$rootJob],
                [$childJob1],
                [$rootJob]
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DuplicateJobException())
            );

        $this->jobManager->expects($this->once())
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

    public function testCreateChildJobShouldThrowIfNameIsEmpty(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job name must not be empty');

        $this->jobProcessor->findOrCreateChildJob('', new Job());
    }

    public function testCreateChildJobShouldFindAndReturnAlreadyCreatedJob(): void
    {
        $job = new Job();
        $job->setId(123);

        $this->jobRepository->expects(self::never())
            ->method('createJob');
        $this->jobManager->expects(self::never())
            ->method('saveJob');
        $this->jobRepository->expects(self::once())
            ->method('findChildJobByName')
            ->with('job-name', self::identicalTo($job))
            ->willReturn($job);

        $result = $this->jobProcessor->findOrCreateChildJob('job-name', $job);

        self::assertSame($job, $result);
    }

    public function testCreateChildJobShouldCreateAndSaveJobAndPublishRecalculateRootMessage(): void
    {
        $job = new Job();
        $job->setId(12345);

        $this->jobRepository->expects(self::once())
            ->method('createJob')
            ->willReturn($job);
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($job));
        $this->jobRepository->expects(self::once())
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

    public function testStartChildJobShouldThrowIfRootJob(): void
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t start root jobs. id: "12345"');

        $this->jobProcessor->startChildJob($rootJob);
    }

    public function testStartChildJobShouldThrowIfJobHasNotNewStatus(): void
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $this->expectException(JobCannotBeStartedException::class);
        $this->expectExceptionMessage(
            'Job "12345" cannot be started because it is already in status "oro.message_queue_job.status.cancelled"'
        );

        $this->jobProcessor->startChildJob($job);
    }

    public function getStatusThatCanRun(): array
    {
        return [
            [Job::STATUS_NEW],
            [Job::STATUS_FAILED_REDELIVERED],
        ];
    }

    /**
     * @dataProvider getStatusThatCanRun
     */
    public function testStartJobShouldUpdateJobWithRunningStatusAndStartAtTime(string $jobStatus): void
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

    public function testSuccessChildJobShouldThrowIfRootJob(): void
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t success root jobs. id: "12345"');

        $this->jobProcessor->successChildJob($rootJob);
    }

    public function testSuccessJobShouldUpdateJobWithSuccessStatusAndStopAtTime(): void
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

    public function testFailChildJobShouldThrowIfRootJob(): void
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t fail root jobs. id: "12345"');

        $this->jobProcessor->failChildJob($rootJob);
    }

    public function testFailJobShouldUpdateJobWithFailStatusAndStopAtTime(): void
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

        $this->jobManager->expects(self::once())
            ->method('saveJobWithLock')
            ->willReturnCallback(static function (Job $job, $callback) {
                $callback($job);
            });

        $this->jobManager->expects(self::exactly(2))
            ->method('setCancelledStatusForChildJobs')
            ->withConsecutive(
                [$rootJob, [Job::STATUS_NEW]],
                [$rootJob, [Job::STATUS_FAILED_REDELIVERED]]
            );

        $this->jobProcessor->interruptRootJob($rootJob);

        self::assertTrue($rootJob->isInterrupted());
        self::assertNotNull($rootJob->getStoppedAt());
    }

    public function testFailAndRedeliveryChildJob(): void
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $this->jobProcessor->failAndRedeliveryChildJob($job);

        self::assertEquals(Job::STATUS_FAILED_REDELIVERED, $job->getStatus());
    }

    private function configureBaseMocksForStaleJobsCases(
        Job $job,
        int $timeForStale = 0,
        Job $rootJobFoundByStorage = null
    ): JobConfigurationProviderInterface {
        $jobConfigurationProvider = $this->createMock(JobConfigurationProviderInterface::class);
        $jobConfigurationProvider->expects(self::any())
            ->method('getTimeBeforeStaleForJobName')
            ->willReturn($timeForStale);

        $this->jobRepository->expects(self::once())
            ->method('createJob')
            ->willReturn($job);

        $this->jobRepository->expects(self::once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn($rootJobFoundByStorage);

        $this->jobRepository->expects(self::once())
            ->method('findRootJobByOwnerIdAndJobName');

        return $jobConfigurationProvider;
    }

    /**
     * @dataProvider getIsRootJobExistsAndNotStaleProvider
     */
    public function testIsRootJobExistsAndNotStale(?Job $job, ?Job $expectedResult): void
    {
        $this->jobRepository->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->with('job-name', [])
            ->willReturn($job);

        self::assertEquals(
            $expectedResult,
            $this->jobProcessor->findNotStaleRootJobyJobNameAndStatuses('job-name', [])
        );
    }

    public function getIsRootJobExistsAndNotStaleProvider(): array
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

    public function testIsRootJobExistsAndNotStaleIfJobStale(): void
    {
        $rootJob = new Job();
        $rootJob->setId(1);
        $rootJob->setChildJobs([]);

        $this->jobRepository->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn($rootJob);
        $this->jobManager->expects($this->once())
            ->method('saveJobWithLock')
            ->with($rootJob);

        $jobConfigurationProvider = $this->createMock(JobConfigurationProviderInterface::class);
        $jobConfigurationProvider->expects($this->any())
            ->method('getTimeBeforeStaleForJobName')
            ->willReturn(0);

        $this->jobProcessor->setJobConfigurationProvider($jobConfigurationProvider);

        self::assertNull($this->jobProcessor->findNotStaleRootJobyJobNameAndStatuses('job-name', []));
    }
}

<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\Topics;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class JobProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeCreatedWithRequiredArguments()
    {
        new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());
    }

    public function testCreateRootJobShouldThrowIfOwnerIdIsEmpty()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('OwnerId must not be empty');

        $processor->findOrCreateRootJob(null, 'job-name', true);
    }

    public function testCreateRootJobShouldThrowIfNameIsEmpty()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job name must not be empty');

        $processor->findOrCreateRootJob('owner-id', null, true);
    }

    public function testShouldCreateRootJobAndReturnIt()
    {
        $job = new Job();

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->identicalTo($job))
        ;
        $storage
            ->expects(static::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name')
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertSame($job, $result);
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
        $this->assertNull($job->getStoppedAt());
        $this->assertEquals('job-name', $job->getName());
        $this->assertEquals('owner-id', $job->getOwnerId());
    }

    public function testShouldCatchDuplicateJobAndReturnNull()
    {
        $job = new Job();

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->identicalTo($job))
            ->will($this->throwException(new DuplicateJobException()))
        ;
        $storage
            ->expects($this->once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name')
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        static::assertNull($result);
    }

    public function testFindOrCreateRootJobFindJobAndReturn()
    {
        $job = new Job();

        $storage = $this->createJobStorage();
        $storage
            ->expects(static::never())
            ->method('createJob');
        $storage
            ->expects(static::never())
            ->method('saveJob');
        $storage
            ->expects(static::once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name')
            ->willReturn($job);

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertSame($job, $result);
    }

    public function testShouldCatchDuplicateCheckIfItIsStaleAndChangeStatus()
    {
        $job = new Job();
        $job->setChildJobs([]);

        list($jobConfigurationProvider, $storage) = $this->configureBaseMocksForStaleJobsCases($job, 0, $job);

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->setJobConfigurationProvider($jobConfigurationProvider);

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertSame($job, $result);
    }

    public function testFindOrCreateReturnsNullIfRootJobInActiveStatusCannotBeFound()
    {
        $job = new Job();
        $job->setChildJobs([]);

        list($jobConfigurationProvider, $storage) = $this->configureBaseMocksForStaleJobsCases($job);

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->setJobConfigurationProvider($jobConfigurationProvider);

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertNull($result);
    }

    public function testFindOrCreateReturnsNullIfRootJobIsNotStaleYet()
    {
        $job = new Job();
        $job->setChildJobs([]);

        list($jobConfigurationProvider, $storage) = $this->configureBaseMocksForStaleJobsCases($job, 100, $job);

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->setJobConfigurationProvider($jobConfigurationProvider);

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertNull($result);
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

        list($jobConfigurationProvider, $storage) = $this->configureBaseMocksForStaleJobsCases($rootJob, 0, $rootJob);

        $storage
            ->method('findJobById')
            ->withConsecutive([1], [11], [12])
            ->willReturnOnConsecutiveCalls($rootJob, $childJob1, $childJob2);

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->setJobConfigurationProvider($jobConfigurationProvider);

        $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertSame(Job::STATUS_STALE, $rootJob->getStatus());
        $this->assertSame(Job::STATUS_STALE, $childJob1->getStatus());
        $this->assertSame(Job::STATUS_SUCCESS, $childJob2->getStatus());
    }

    public function testCreateChildJobShouldThrowIfNameIsEmpty()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job name must not be empty');
        $processor->findOrCreateChildJob(null, new Job());
    }

    public function testCreateChildJobShouldFindAndReturnAlreadyCreatedJob()
    {
        $job = new Job();
        $job->setId(123);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->never())
            ->method('createJob')
        ;
        $storage
            ->expects($this->never())
            ->method('saveJob')
        ;
        $storage
            ->expects($this->once())
            ->method('findChildJobByName')
            ->with('job-name', $this->identicalTo($job))
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(123)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $result = $processor->findOrCreateChildJob('job-name', $job);

        $this->assertSame($job, $result);
    }

    public function testCreateChildJobShouldCreateAndSaveJobAndPublishRecalculateRootMessage()
    {
        $job = new Job();
        $job->setId(12345);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->identicalTo($job))
        ;
        $storage
            ->expects($this->once())
            ->method('findChildJobByName')
            ->with('job-name', $this->identicalTo($job))
            ->will($this->returnValue(null))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $message = new Message();
        $message->setBody(['jobId' => 12345]);
        $message->setPriority(MessagePriority::HIGH);
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [$this->equalTo(Topics::CALCULATE_ROOT_JOB_STATUS), ['jobId' => 12345]],
                [$this->equalTo(Topics::CALCULATE_ROOT_JOB_PROGRESS), $message]
            )
        ;
        $processor = new JobProcessor($storage, $producer);

        $result = $processor->findOrCreateChildJob('job-name', $job);

        $this->assertSame($job, $result);
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
        $this->assertEquals('job-name', $job->getName());
        $this->assertNull($job->getOwnerId());
    }

    public function testStartChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t start root jobs. id: "12345"');
        $processor->startChildJob($rootJob);
    }

    public function testStartChildJobShouldThrowIfJobHasNotNewStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can start only new jobs: id: "12345", status: "oro.message_queue_job.status.cancelled"'
        );

        $processor->startChildJob($job);
    }

    public function getStatusThatCanRun()
    {
        return [
            [Job::STATUS_NEW],
            [Job::STATUS_FAILED_REDELIVERED],
        ];
    }

    /**
     * @dataProvider getStatusThatCanRun
     */
    public function testStartJobShouldUpdateJobWithRunningStatusAndStartAtTime($jobStatus)
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus($jobStatus);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->any())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->startChildJob($job);

        $this->assertEquals(Job::STATUS_RUNNING, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
    }

    public function testSuccessChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t success root jobs. id: "12345"');
        $processor->successChildJob($rootJob);
    }

    public function testSuccessChildJobShouldThrowIfJobHasNotRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can success only running jobs. id: "12345", status: "oro.message_queue_job.status.cancelled"'
        );
        $processor->successChildJob($job);
    }

    public function testSuccessJobShouldUpdateJobWithSuccessStatusAndStopAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->any())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->any())
            ->method('send')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->successChildJob($job);

        $this->assertEquals(Job::STATUS_SUCCESS, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
    }

    public function testFailChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t fail root jobs. id: "12345"');
        $processor->failChildJob($rootJob);
    }

    public function testFailChildJobShouldThrowIfJobHasNotRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can fail only running jobs. id: "12345", status: "oro.message_queue_job.status.cancelled"'
        );

        $processor->failChildJob($job);
    }

    public function testFailJobShouldUpdateJobWithFailStatusAndStopAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->exactly(2))
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->exactly(2))
            ->method('send')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->failChildJob($job);

        $this->assertEquals(Job::STATUS_FAILED, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
    }

    public function testCancelChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can\'t cancel root jobs. id: "12345"');
        $processor->cancelChildJob($rootJob);
    }

    public function testCancelChildJobShouldThrowIfJobHasNotNewOrRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can cancel only new or running jobs. id: "12345", status: "oro.message_queue_job.status.cancelled"'
        );

        $processor->cancelChildJob($job);
    }

    public function testCancelJobShouldUpdateJobWithCancelStatusAndStoppedAtTimeAndStartedAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_NEW);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->any())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->any())
            ->method('send')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->cancelChildJob($job);

        $this->assertEquals(Job::STATUS_CANCELLED, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
    }

    public function testInterruptRootJobShouldThrowIfNotRootJob()
    {
        $notRootJob = new Job();
        $notRootJob->setId(123);
        $notRootJob->setRootJob(new Job());

        $processor = new JobProcessor($this->createJobStorage(), $this->createMessageProducerMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can interrupt only root jobs. id: "123"');

        $processor->interruptRootJob($notRootJob);
    }


    public function testInterruptRootJobShouldCancelChildrenJobIfRunWithForce()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $childJob = new Job();
        $childJob->setId(1234);
        $childJob->setStatus(Job::STATUS_NEW);
        $childJob->setRootJob($rootJob);
        $rootJob->setChildJobs([$childJob]);
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->at(0))
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }))
        ;
        $storage
            ->expects($this->at(1))
            ->method('saveJob')
            ->with($childJob)
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(1234)
            ->will($this->returnValue($childJob))
        ;
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->at(0))
            ->method('send')
            ->with(Topics::CALCULATE_ROOT_JOB_STATUS, ['jobId' => 1234])
        ;

        $message = new Message(['jobId' => 1234], MessagePriority::HIGH);
        $producer
            ->expects($this->at(1))
            ->method('send')
            ->with(Topics::CALCULATE_ROOT_JOB_PROGRESS, $message)
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->interruptRootJob($rootJob, true);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
        $this->assertEquals($childJob->getStatus(), Job::STATUS_CANCELLED);
    }

    public function testInterruptRootJobShouldDoNothingIfAlreadyInterrupted()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setInterrupted(true);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->never())
            ->method('saveJob')
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->interruptRootJob($rootJob);
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrueAndCancelNonRunnedChildren()
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

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->at(0))
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }));
        $storage
            ->expects($this->at(1))
            ->method('saveJob')
            ->with($childNewJob);
        $storage
            ->expects($this->at(4))
            ->method('saveJob')
            ->with($childRedeliveredJob);

        $storage
            ->expects($this->exactly(2))
            ->method('findJobById')
            ->will($this->returnCallback(function ($jobId) use ($childRunnedJob, $childNewJob, $childRedeliveredJob) {
                $jobs = [
                    $childNewJob->getId() => $childNewJob,
                    $childRedeliveredJob->getId() => $childRedeliveredJob,
                ];

                return $jobs[$jobId];
            }));

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->interruptRootJob($rootJob);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertNull($rootJob->getStoppedAt());
        $this->assertEquals(Job::STATUS_RUNNING, $childRunnedJob->getStatus());
        $this->assertEquals(Job::STATUS_CANCELLED, $childNewJob->getStatus());
        $this->assertEquals(Job::STATUS_CANCELLED, $childRedeliveredJob->getStatus());
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrueAndStoppedTimeIfForceTrue()
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }))
        ;

        $processor = new JobProcessor($storage, $this->createMessageProducerMock());
        $processor->interruptRootJob($rootJob, true);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
    }

    public function testFailAndRedeliveryChildJob()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);


        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_ROOT_JOB_STATUS, ['jobId' => 12345])
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->failAndRedeliveryChildJob($job);

        $this->assertEquals(Job::STATUS_FAILED_REDELIVERED, $job->getStatus());
    }

    public function testFailAndRedeliveryChildJobShouldThrowNotRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_FAILED_REDELIVERED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
        ;
        $processor = new JobProcessor($storage, $producer);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Can fail and redelivery only running jobs. id: "12345", ' .
            'status: "oro.message_queue_job.status.failed_redelivered"'
        );

        $processor->failAndRedeliveryChildJob($job);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorage()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobConfigurationProviderInterface
     */
    private function createJobConfigurationProviderMock()
    {
        return $this->createMock(JobConfigurationProviderInterface::class);
    }

    /**
     * @param Job      $job
     * @param int      $timeForStale
     * @param Job|null $rootJobFoundByStorage
     * @return array
     */
    private function configureBaseMocksForStaleJobsCases(
        Job $job,
        int $timeForStale = 0,
        $rootJobFoundByStorage = null
    ): array {
        $jobConfigurationProvider = $this->createJobConfigurationProviderMock();
        $jobConfigurationProvider
            ->expects($this->any())
            ->method('getTimeBeforeStaleForJobName')
            ->will($this->returnValue($timeForStale));

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job));

        $storage
            ->method('saveJob')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DuplicateJobException()),
                $this->returnCallback(function (Job $job, $callback) {
                    $callback($job);
                })
            );

        $storage
            ->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn($rootJobFoundByStorage);

        $storage
            ->expects($this->once())
            ->method('findRootJobByOwnerIdAndJobName');

        return [$jobConfigurationProvider, $storage];
    }
}

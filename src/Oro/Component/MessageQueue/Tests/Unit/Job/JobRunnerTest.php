<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Exception\JobNotFoundException;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Exception\StaleJobRuntimeException;
use Oro\Component\MessageQueue\Job\Extension\ExtensionInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Tests\Unit\Stub\JobAwareTopicInterfaceStub;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class JobRunnerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var JobProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobProcessor;

    /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobExtension;

    /** @var TopicRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $topicRegistry;

    /** @var JobRunner */
    private $jobRunner;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobProcessor = $this->createMock(JobProcessor::class);
        $this->jobExtension = $this->createMock(ExtensionInterface::class);
        $this->topicRegistry = $this->createMock(TopicRegistry::class);

        $this->jobRunner = new JobRunner($this->jobProcessor, $this->jobExtension, $this->topicRegistry);
    }

    public function testRunUniqueRootJobNotFound()
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = null;

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('findOrCreateChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPreRunUnique');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunUnique');

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () {
        });

        $this->assertNull($result);
    }

    public function testRunUniqueRootJobIsStale()
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1, 'status' => 'oro.message_queue_job.status.stale']);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('findOrCreateChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPreRunUnique');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunUnique');

        $this->expectException(StaleJobRuntimeException::class);
        $this->expectExceptionMessage('Cannot run jobs in status stale, id: "1"');

        $this->jobRunner->runUnique($ownerId, $jobName, function () {
        });
    }

    public function testRunUniqueCanceled(): void
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1, 'interrupted' => true]);
        $childJob = $this->getEntity(
            Job::class,
            ['id' => 2, 'rootJob' => $rootJob, 'status' => Job::STATUS_CANCELLED]
        );

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->once())
            ->method('onCancel')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPreRunUnique');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunUnique');

        $result = $this->jobRunner->runUnique($ownerId, $jobName, static function ($callback, Job $job) {
            $callback($job);
        });

        $this->assertNull($result);
    }

    public function testRunUniqueChildJobIsReadyToStart()
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunUnique')
            ->with($childJob);

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () {
        });

        $this->assertNull($result);
    }

    /**
     * @dataProvider resultSuccessDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunUniqueChildJobIsReadyToStopSuccess($expectedResult)
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('successChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunUnique')
            ->with($childJob);

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunUniqueChildJobIsReadyToStopFailed($expectedResult)
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunUnique')
            ->with($childJob);

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultSuccessDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunUniqueChildJobFailedRedeliveredSuccess($expectedResult)
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
            'status' => 'oro.message_queue_job.status.failed_redelivered',
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('successChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunUnique')
            ->with($childJob);
        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunUniqueChildJobFailedRedeliveredFailed($expectedResult)
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
            'status' => 'oro.message_queue_job.status.failed_redelivered',
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunUnique')
            ->with($childJob);

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    public function testRunUniqueCallbackThrowException()
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunUnique');

        $this->expectException(JobRuntimeException::class);
        $this->expectExceptionMessage('An error occurred while running job, id: 2');

        $this->jobRunner->runUnique($ownerId, $jobName, function () {
            throw new \Exception('Exception Message');
        });
    }

    public function testRunUniqueCallbackThrowError()
    {
        $ownerId = uniqid('test', false);
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with($ownerId, $jobName, true)
            ->willReturn($rootJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunUnique')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunUnique');

        $this->expectException(JobRuntimeException::class);
        $this->expectExceptionMessage('An error occurred while running job, id: 2');

        $this->jobRunner->runUnique($ownerId, $jobName, function () {
            $func = function (array $a) {
            };

            call_user_func($func, 1);
        });
    }

    /**
     * @dataProvider resultSuccessDataProvider
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testCreateDelayed($expectedResult)
    {
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreCreateDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostCreateDelayed')
            ->with($childJob);

        $jobRunner = new JobRunner($this->jobProcessor, $this->jobExtension, $this->topicRegistry, $rootJob);
        $result = $jobRunner->createDelayed($jobName, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    public function testCreateDelayedException()
    {
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreCreateDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostCreateDelayed');

        $this->expectException(JobRuntimeException::class);
        $this->expectExceptionMessage('An error occurred while created job, id: 2');

        $jobRunner = new JobRunner($this->jobProcessor, $this->jobExtension, $this->topicRegistry, $rootJob);
        $jobRunner->createDelayed($jobName, function () {
            throw new \Exception('Exception Message');
        });
    }

    public function testCreateDelayedError()
    {
        $jobName = 'job_name';
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => 2,
            'rootJob' => $rootJob,
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with($jobName, $rootJob)
            ->willReturn($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreCreateDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostCreateDelayed');

        $this->expectException(JobRuntimeException::class);
        $this->expectExceptionMessage('An error occurred while created job, id: 2');

        $jobRunner = new JobRunner($this->jobProcessor, $this->jobExtension, $this->topicRegistry, $rootJob);
        $jobRunner->createDelayed($jobName, function () {
            $func = function (array $a) {
            };

            call_user_func($func, 1);
        });
    }

    public function testRunDelayedChildJobNotFound()
    {
        $jobId = 2;
        $childJob = null;

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPreRunDelayed');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunDelayed');

        $this->expectException(JobNotFoundException::class);
        $this->expectExceptionMessage('Job was not found. id: "2"');

        $this->jobRunner->runDelayed($jobId, function () {
        });
    }

    public function testRunDelayedChildJobIsStale()
    {
        $jobId = 2;
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'status' => 'oro.message_queue_job.status.stale',
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPreRunDelayed');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunDelayed');

        $this->expectException(StaleJobRuntimeException::class);
        $this->expectExceptionMessage('Cannot run jobs in status stale, id: "2"');

        $this->jobRunner->runDelayed($jobId, function () {
        });
    }

    public function testRunDelayedCanceled(): void
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1, 'interrupted' => true]);
        $childJob = $this->getEntity(
            Job::class,
            ['id' => $jobId, 'rootJob' => $rootJob, 'status' => Job::STATUS_CANCELLED]
        );

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->once())
            ->method('onCancel')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPreRunDelayed');

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunDelayed');

        $result = $this->jobRunner->runDelayed($jobId, static function ($callback, Job $job) {
            $callback($job);
        });

        $this->assertNull($result);
    }

    public function testRunDelayedChildJobIsReadyToStart()
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($childJob);

        $result = $this->jobRunner->runDelayed($jobId, function () {
        });

        $this->assertNull($result);
    }

    /**
     * @dataProvider resultSuccessDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunDelayedChildJobIsReadyToStopSuccess($expectedResult)
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('successChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($childJob);

        $result = $this->jobRunner->runDelayed($jobId, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunDelayedChildJobIsReadyToStopFailed($expectedResult)
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($childJob);

        $result = $this->jobRunner->runDelayed($jobId, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultSuccessDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunDelayedChildJobFailedRedeliveredSuccess($expectedResult)
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
            'status' => 'oro.message_queue_job.status.failed_redelivered',
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('successChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($childJob);

        $result = $this->jobRunner->runDelayed($jobId, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunDelayedChildJobFailedRedeliveredFailed($expectedResult)
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
            'status' => 'oro.message_queue_job.status.failed_redelivered',
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($childJob);

        $result = $this->jobRunner->runDelayed($jobId, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    public function testRunDelayedCallbackThrowException()
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunDelayed');

        $this->expectException(JobRuntimeException::class);
        $this->expectExceptionMessage('An error occurred while running job, id: 2');

        $this->jobRunner->runDelayed($jobId, function () {
            throw new \Exception('Exception Message');
        });
    }

    public function testRunDelayedCallbackThrowRedeliveryException()
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->once())
            ->method('failAndRedeliveryChildJob')
            ->with($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->never())
            ->method('onError');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunDelayed');

        $this->expectException(JobRedeliveryException::class);
        $this->jobRunner->runDelayed($jobId, function () {
            throw JobRedeliveryException::create();
        });
    }

    public function testRunDelayedCallbackThrowError()
    {
        $jobId = 2;
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, [
            'id' => $jobId,
            'rootJob' => $rootJob,
            'startedAt' => new \DateTime(),
            'stoppedAt' => new \DateTime(),
        ]);

        $this->jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($childJob);

        $this->jobProcessor
            ->expects($this->never())
            ->method('startChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('successChildJob');

        $this->jobProcessor
            ->expects($this->never())
            ->method('failChildJob');

        $this->jobExtension
            ->expects($this->never())
            ->method('onCancel');

        $this->jobExtension
            ->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($childJob);

        $this->jobExtension
            ->expects($this->never())
            ->method('onPostRunDelayed');

        $this->expectException(JobRuntimeException::class);
        $this->expectExceptionMessage('An error occurred while running job, id: 2');

        $this->jobRunner->runDelayed($jobId, function () {
            $func = function (array $a) {
            };

            call_user_func($func, 1);
        });
    }

    public function testGetJobRunnerForChildJob()
    {
        $rootJob = $this->getEntity(Job::class, ['id' => 1]);
        $jobRunnerForChildJob = $this->jobRunner->getJobRunnerForChildJob($rootJob);
        $this->assertInstanceOf(JobRunner::class, $jobRunnerForChildJob);
        $this->assertNotSame($this->jobRunner, $jobRunnerForChildJob);
    }

    /**
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunUniqueByMessageWithJobNameInProperty($expectedResult)
    {
        $jobName = 'job_name';
        $messageId = 'id1';

        $message = new Message();
        $message->setProperties([
            JobAwareTopicInterface::UNIQUE_JOB_NAME => $jobName
        ]);
        $message->setMessageId($messageId);

        $this->jobProcessor->expects($this->once())
            ->method('findJobByName')
            ->with($messageId, $jobName)
            ->willReturn(null);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function () use ($expectedResult) {
                return $expectedResult;
            }
        );
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider resultFailedDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testRunUniqueByMessageWithoutJobNameInProperty($expectedResult)
    {
        $jobName = 'job_name';
        $topicName = 'topic_name';
        $messageId = 'id1';
        $body = [];

        $topic = $this->createMock(JobAwareTopicInterfaceStub::class);
        $message = new Message();
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => $topicName
        ]);
        $message->setBody($body);
        $message->setMessageId($messageId);

        $this->topicRegistry->expects(self::once())
            ->method('get')
            ->with($topicName)
            ->willReturn($topic);

        $topic->expects(self::once())
            ->method('createJobName')
            ->with($body)
            ->willReturn($jobName);

        $this->jobProcessor->expects($this->once())
            ->method('findJobByName')
            ->with($messageId, $jobName)
            ->willReturn(null);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function () use ($expectedResult) {
                return $expectedResult;
            }
        );
        $this->assertEquals($expectedResult, $result);
    }

    public function testRunUniqueByMessageThrowException()
    {
        $topicName = 'topic_name';

        $topic = $this->createMock(TopicInterface::class);
        $message = new Message();
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => $topicName,
        ]);

        $this->topicRegistry->expects(self::once())
            ->method('get')
            ->with($topicName)
            ->willReturn($topic);

        $this->jobProcessor->expects($this->never())
            ->method('findOrCreateRootJob');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf('Topic %s must implement JobAwareTopicInterface', get_class($topic))
        );

        $this->jobRunner->runUniqueByMessage(
            $message,
            function () {
                throw new \Exception('Exception Message');
            }
        );
    }

    public function resultSuccessDataProvider(): array
    {
        return [
            'bool' => [true],
            'int' => [1],
            'string' => ['test'],
            'object' => [new \stdClass()],
            'callback' => [
                function () {
                },
            ],
        ];
    }

    public function resultFailedDataProvider(): array
    {
        return [
            'bool' => [false],
            'int' => [0],
            'null' => [null],
            'string' => [''],
        ];
    }
}

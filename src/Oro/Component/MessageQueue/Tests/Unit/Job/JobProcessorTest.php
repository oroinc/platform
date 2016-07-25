<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobStorage;

class JobProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeCreatedWithRequiredArguments()
    {
        new JobProcessor($this->createJobStorage());
    }

    public function testCreateJobShouldThrowIfRootJobIsNotRoot()
    {
        $notRootJob = new Job();
        $notRootJob->setId(12345);
        $notRootJob->setRootJob(new Job());

        $this->setExpectedException(
            \LogicException::class,
            'You can append jobs only to root job but it is not. id: "12345"'
        );

        $processor = new JobProcessor($this->createJobStorage());
        $processor->createJob('name', $notRootJob);
    }

    public function testCreateJobShouldThrowIfSetRootAndUnique()
    {
        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(
            \LogicException::class,
            'Can create only root unique jobs.'
        );

        $processor = new JobProcessor($this->createJobStorage());
        $processor->createJob('name', $rootJob, true);
    }

    public function testCreateJobShouldCreateRootJob()
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;

        $processor = new JobProcessor($storage);
        $job = $processor->createJob('name');

        $this->assertEquals('name', $job->getName());
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertNull($job->getRootJob());
        $this->assertFalse($job->isUnique());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
    }

    public function testCreateJobShouldCreateChildJob()
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;

        $rootJob = new Job();

        $processor = new JobProcessor($storage);
        $job = $processor->createJob('name', $rootJob);

        $this->assertEquals('name', $job->getName());
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertSame($rootJob, $job->getRootJob());
        $this->assertFalse($job->isUnique());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
    }

    public function testCreateJobShouldCreateUniqueRootJob()
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;

        $processor = new JobProcessor($storage);
        $job = $processor->createJob('name', null, true);

        $this->assertEquals('name', $job->getName());
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertNull($job->getRootJob());
        $this->assertTrue($job->isUnique());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
    }

    public function testCreateJobShouldReturnFalseIfJobIsUniqueAndAlreadyExists()
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
            ->will($this->throwException(new DuplicateJobException()))
        ;

        $processor = new JobProcessor($storage);
        $job = $processor->createJob('name', null, true);

        $this->assertNull($job);
    }

    public function testCreateJobShouldNotCatchDuplicateJobExceptionIfNotUnique()
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
            ->will($this->throwException(new DuplicateJobException()))
        ;

        $processor = new JobProcessor($storage);

        $this->setExpectedException(DuplicateJobException::class);

        $processor->createJob('name');
    }

    public function testStartChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t start root jobs. id: "12345"');

        $processor->startChildJob($rootJob);
    }

    public function testStartChildJobShouldThrowIfJobHasNotNewStatus()
    {
        $processor = new JobProcessor($this->createJobStorage());

        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $this->setExpectedException(
            \LogicException::class,
            'Can start only new jobs: id: "12345", status: "oro.job.status.cancelled"'
        );

        $processor->startChildJob($job);
    }

    public function testStartJobShouldUpdateJobWithRunningStatusAndStartAtTime()
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;

        $job = new Job();
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_NEW);

        $processor = new JobProcessor($storage);
        $processor->startChildJob($job);

        $this->assertEquals(Job::STATUS_RUNNING, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
    }

    public function testStopChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t stop root jobs. id: "12345"');

        $processor->stopChildJob($rootJob, 'status');
    }

    public function testStopChildJobShouldThrowIfJobHasNotRunningStatus()
    {
        $processor = new JobProcessor($this->createJobStorage());

        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $this->setExpectedException(
            \LogicException::class,
            'Can stop only running jobs. id: "12345", status: "oro.job.status.cancelled"'
        );

        $processor->stopChildJob($job, 'status');
    }

    public function testStopChildJobShouldThrowIfStatusIsNotOneOfStopStatuses()
    {
        $processor = new JobProcessor($this->createJobStorage());

        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $this->setExpectedException(
            \LogicException::class,
            'This status is not valid stop status. id: "12345", '.
            'status: "not-stop-status", valid: [oro.job.status.success, '.
            'oro.job.status.failed, oro.job.status.cancelled]'
        );

        $processor->stopChildJob($job, 'not-stop-status');
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
    public function testStopJobShouldUpdateJobWithStopStatusAndStopAtTime($stopStatus)
    {
        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;

        $job = new Job();
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $processor = new JobProcessor($storage);
        $processor->stopChildJob($job, $stopStatus);

        $this->assertEquals($stopStatus, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
    }

    public function testInterruptRootJobShouldThrowIfNotRootJob()
    {
        $notRootJob = new Job();
        $notRootJob->setId(123);
        $notRootJob->setRootJob(new Job());

        $processor = new JobProcessor($this->createJobStorage());

        $this->setExpectedException(\LogicException::class, 'Can interrupt only root jobs. id: "123"');

        $processor->interruptRootJob($notRootJob);
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

        $processor = new JobProcessor($storage);
        $processor->interruptRootJob($rootJob);
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrue()
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

        $processor = new JobProcessor($storage);
        $processor->interruptRootJob($rootJob);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertNull($rootJob->getStoppedAt());
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

        $processor = new JobProcessor($storage);
        $processor->interruptRootJob($rootJob, true);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorage()
    {
        return $this->getMock(JobStorage::class, [], [], '', false);
    }
}

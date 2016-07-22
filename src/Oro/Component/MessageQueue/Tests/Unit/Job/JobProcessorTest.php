<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;

class JobProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeCreatedWithRequiredArguments()
    {
        new JobProcessor($this->createEntityManagerMock());
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

        $processor = new JobProcessor($this->createEntityManagerMock());
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

        $processor = new JobProcessor($this->createEntityManagerMock());
        $processor->createJob('name', $rootJob, true);
    }

    public function testCreateJobShouldCreateRootJob()
    {
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $processor = new JobProcessor($em);
        $job = $processor->createJob('name');

        $this->assertEquals('name', $job->getName());
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertNull($job->getRootJob());
        $this->assertNull($job->getUniqueName());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
    }

    public function testCreateJobShouldCreateChildJob()
    {
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $rootJob = new Job();

        $processor = new JobProcessor($em);
        $job = $processor->createJob('name', $rootJob);

        $this->assertEquals('name', $job->getName());
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertSame($rootJob, $job->getRootJob());
        $this->assertNull($job->getUniqueName());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
    }

    public function testCreateJobShouldCreateUniqueRootJob()
    {
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $processor = new JobProcessor($em);
        $job = $processor->createJob('name', null, true);

        $this->assertEquals('name', $job->getName());
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertNull($job->getRootJob());
        $this->assertEquals('name', $job->getUniqueName());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
    }

    public function testCreateJobShouldReturnFalseIfJobIsUniqueAndAlreadyExists()
    {
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
            ->will($this->throwException($this->createUniqueConstraintViolationExceptionMock()))
        ;

        $processor = new JobProcessor($em);
        $job = $processor->createJob('name', null, true);

        $this->assertNull($job);
    }

    public function testCreateJobShouldNotCatchUniqueConstraintViolationExceptionIfNotUnique()
    {
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
            ->will($this->throwException($this->createUniqueConstraintViolationExceptionMock()))
        ;

        $processor = new JobProcessor($em);

        $this->setExpectedException(UniqueConstraintViolationException::class);
        $processor->createJob('name');
    }

    public function testStartChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createEntityManagerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t start root jobs. id: "12345"');

        $processor->startChildJob($rootJob);
    }

    public function testStartChildJobShouldThrowIfJobHasNotNewStatus()
    {
        $processor = new JobProcessor($this->createEntityManagerMock());

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
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $job = new Job();
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_NEW);

        $processor = new JobProcessor($em);
        $processor->startChildJob($job);

        $this->assertEquals(Job::STATUS_RUNNING, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
    }

    public function testStopChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createEntityManagerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t stop root jobs. id: "12345"');

        $processor->stopChildJob($rootJob, 'status');
    }

    public function testStopChildJobShouldThrowIfJobHasNotRunningStatus()
    {
        $processor = new JobProcessor($this->createEntityManagerMock());

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
        $processor = new JobProcessor($this->createEntityManagerMock());

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
        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $job = new Job();
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $processor = new JobProcessor($em);
        $processor->stopChildJob($job, $stopStatus);

        $this->assertEquals($stopStatus, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
    }

    public function testInterruptRootJobShouldThrowIfNotRootJob()
    {
        $notRootJob = new Job();
        $notRootJob->setId(123);
        $notRootJob->setRootJob(new Job());

        $processor = new JobProcessor($this->createEntityManagerMock());

        $this->setExpectedException(\LogicException::class, 'Can interrupt only root jobs. id: "123"');

        $processor->interruptRootJob($notRootJob);
    }

    public function testInterruptRootJobShouldDoNothingIfAlreadyInterrupted()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setInterrupted(true);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->never())
            ->method('transactional')
        ;

        $processor = new JobProcessor($em);
        $processor->interruptRootJob($rootJob);
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrue()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setUniqueName('name');

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->with(Job::class, 123, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($rootJob))
        ;

        $processor = new JobProcessor($em);
        $processor->interruptRootJob($rootJob);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertNull($rootJob->getStoppedAt());
        $this->assertEquals('name', $rootJob->getUniqueName());
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrueAndUniqueNameNullAndStoppedTimeIfForceTrue()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setUniqueName('name');

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->with(Job::class, 123, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($rootJob))
        ;

        $processor = new JobProcessor($em);
        $processor->interruptRootJob($rootJob, true);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
        $this->assertNull($rootJob->getUniqueName());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->getMock(EntityManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UniqueConstraintViolationException
     */
    private function createUniqueConstraintViolationExceptionMock()
    {
        return $this->getMock(UniqueConstraintViolationException::class, [], [], '', false);
    }
}

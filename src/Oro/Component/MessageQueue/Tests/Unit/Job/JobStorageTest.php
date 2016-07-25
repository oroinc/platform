<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\JobEntity;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

class JobStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new JobStorage($this->createEntityManagerMock());
    }

    public function testShouldThrowIfGotUnexpectedJobInstance()
    {
        $storage = new JobStorage($this->createEntityManagerMock());

        $this->setExpectedException(
            \LogicException::class,
            'Got unexpected job instance: expected: "Oro\Component\MessageQueue\Job\EntityJob", '.
            'actual" "Oro\Component\MessageQueue\Job\Job"'
        );

        $storage->saveJob(new Job());
    }

    public function testShouldSaveJobWithoutLockIfThereIsNoCallback()
    {
        $job = new JobEntity();

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($job))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;
        $em
            ->expects($this->never())
            ->method('transactional')
        ;

        $storage = new JobStorage($em);
        $storage->saveJob($job);
    }

    public function testShouldSaveJobWithLockIfWithCallback()
    {
        $job = new JobEntity();

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->never())
            ->method('persist')
            ->with($this->identicalTo($job))
        ;
        $em
            ->expects($this->never())
            ->method('flush')
        ;
        $em
            ->expects($this->once())
            ->method('transactional')
        ;

        $storage = new JobStorage($em);
        $storage->saveJob($job, function () {

        });
    }

    public function testShouldCatchUniqueConstraintViolationExceptionAndThrowDuplicateJobException()
    {
        $job = new JobEntity();

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($job))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
            ->will($this->throwException($this->createUniqueConstraintViolationExceptionMock()))
        ;

        $storage = new JobStorage($em);

        $this->setExpectedException(DuplicateJobException::class);

        $storage->saveJob($job);
    }

    public function testShouldLockEntityAndPassNewInstanceIntoCallback()
    {
        $job = new JobEntity();
        $job->setId(12345);
        $lockedJob = new JobEntity();

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
            ->with(JobEntity::class, 12345, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($lockedJob))
        ;

        $storage = new JobStorage($em);
        $resultJob = null;
        $storage->saveJob($job, function (Job $job) use (&$resultJob) {
            $resultJob = $job;
        });

        $this->assertSame($lockedJob, $resultJob);
    }

    public function testShouldSetUniqueNameIfJobIsUniqueAndStatusNew()
    {
        $job = new JobEntity();
        $job->setName('job-name');
        $job->setUnique(true);
        $job->setStatus(Job::STATUS_NEW);

        $storage = new JobStorage($this->createEntityManagerMock());
        $storage->saveJob($job);

        $this->assertEquals('job-name', $job->getUniqueName());
        $this->assertEquals('job-name', $job->getName());
    }

    public function testShouldUnsetUniqueNameIfJobIsUniqueAndStoppedAtIsSet()
    {
        $job = new JobEntity();
        $job->setName('job-name');
        $job->setUniqueName('unique-name');
        $job->setUnique(true);
        $job->setStoppedAt(new \DateTime());

        $storage = new JobStorage($this->createEntityManagerMock());
        $storage->saveJob($job);

        $this->assertEquals('job-name', $job->getName());
        $this->assertNull($job->getUniqueName());
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

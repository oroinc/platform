<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

class JobStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new JobStorage($this->createEntityManagerMock(), $this->createRepositoryMock(), 'unique_table');
    }

    public function testShouldCreateJobObject()
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $storage = new JobStorage($this->createEntityManagerMock(), $repository, 'unique_table');
        $job = $storage->createJob();

        $this->assertInstanceOf(Job::class, $job);
    }

    public function testShouldThrowIfGotUnexpectedJobInstance()
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue('expected\class\name'))
        ;

        $storage = new JobStorage($this->createEntityManagerMock(), $repository, 'unique_table');

        $this->setExpectedException(
            \LogicException::class,
            'Got unexpected job instance: expected: "expected\class\name", '.
            'actual" "Oro\Component\MessageQueue\Job\Job"'
        );

        $storage->saveJob(new Job());
    }

    public function testShouldSaveJobWithoutLockIfThereIsNoCallback()
    {
        $job = new Job();

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

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $storage = new JobStorage($em, $repository, 'unique_table');
        $storage->saveJob($job);
    }

    public function testShouldSaveJobWithLockIfWithCallback()
    {
        $job = new Job();
        $job->setId(1234);

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

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $storage = new JobStorage($em, $repository, 'unique_table');
        $storage->saveJob($job, function () {

        });
    }

    public function testShouldCatchUniqueConstraintViolationExceptionAndThrowDuplicateJobException()
    {
        $job = new Job();
        $job->setUnique(true);

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }))
        ;
        $connection
            ->expects($this->once())
            ->method('insert')
            ->will($this->throwException($this->createUniqueConstraintViolationExceptionMock()))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $storage = new JobStorage($em, $repository, 'unique_table');

        $this->setExpectedException(DuplicateJobException::class);

        $storage->saveJob($job);
    }

    public function testShouldThrowIfTryToSaveNewEntityWithLock()
    {
        $job = new Job();

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $storage = new JobStorage($this->createEntityManagerMock(), $repository, 'unique_table');

        $this->setExpectedException(
            \LogicException::class,
            'Is not possible to create new job with lock, only update is allowed'
        );

        $storage->saveJob($job, function () {
        });
    }

    public function testShouldLockEntityAndPassNewInstanceIntoCallback()
    {
        $job = new Job();
        $job->setId(12345);
        $lockedJob = new Job();

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(12345, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($lockedJob))
        ;

        $storage = new JobStorage($em, $repository, 'unique_table');
        $resultJob = null;
        $storage->saveJob($job, function (Job $job) use (&$resultJob) {
            $resultJob = $job;
        });

        $this->assertSame($lockedJob, $resultJob);
    }

    public function testShouldInsertIntoUniqueTableIfJobIsUniqueAndNew()
    {
        $job = new Job();
        $job->setName('job-name');
        $job->setUnique(true);

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }))
        ;
        $connection
            ->expects($this->once())
            ->method('insert')
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;
        $em
            ->expects($this->once())
            ->method('persist')
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $storage = new JobStorage($em, $repository, 'unique_table');
        $storage->saveJob($job);
    }

    public function testShouldDeleteRecordFromUniqueTableIfJobIsUniqueAndStoppedAtIsSet()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setName('job-name');
        $job->setUnique(true);
        $job->setStoppedAt(new \DateTime());

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('delete')
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;
        $repository
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($job))
        ;

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
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $storage = new JobStorage($em, $repository, 'unique_table');
        $storage->saveJob($job, function () {

        });
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->getMock(Connection::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->getMock(EntityManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    private function createRepositoryMock()
    {
        return $this->getMock(EntityRepository::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UniqueConstraintViolationException
     */
    private function createUniqueConstraintViolationExceptionMock()
    {
        return $this->getMock(UniqueConstraintViolationException::class, [], [], '', false);
    }
}

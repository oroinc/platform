<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Tests\Unit\Mock\JobEntity;

class JobStorageTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    private $doctrine;

    /** @var JobStorage */
    private $storage;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->storage = new JobStorage($this->doctrine, JobEntity::class, 'unique_table');
    }

    public function testShouldCreateJobObject()
    {
        $job = $this->storage->createJob();

        $this->assertEquals(JobEntity::class, get_class($job));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected job instance of "Oro\Component\MessageQueue\Tests\Unit\Mock\JobEntity", given "Oro\Component\MessageQueue\Job\Job".
     */
    // @codingStandardsIgnoreEnd
    public function testShouldThrowIfGotUnexpectedJobInstance()
    {
        $this->storage->saveJob(new Job());
    }

    public function testShouldSaveJobWithoutLockIfThereIsNoCallbackAndChildJob()
    {
        $job = new JobEntity();

        $child = new JobEntity();
        $child->setRootJob($job);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $connection->expects($this->never())
            ->method('transactional');

        $em = $this->createEntityManagerMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));
        $em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($child));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($child));
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $this->storage->saveJob($child);
    }

    public function testShouldSaveJobWithLockCallback()
    {
        $job = new JobEntity();
        $job->setId(1234);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $connection->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }));

        $em = $this->createEntityManagerMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $em->expects($this->once())
            ->method('find')
            ->with(JobEntity::class, 1234, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($job));
        $em->expects($this->never())
            ->method('persist');
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($job));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $this->storage->saveJob(
            $job,
            function () {
            }
        );
    }

    /**
     * @expectedException \Oro\Component\MessageQueue\Job\DuplicateJobException
     * @expectedExceptionMessage Duplicate job. ownerId:"owner-id", name:"job-name"
     */
    public function testShouldCatchUniqueConstraintViolationExceptionAndThrowDuplicateJobException()
    {
        $job = new JobEntity();
        $job->setOwnerId('owner-id');
        $job->setName('job-name');
        $job->setUnique(true);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $connection->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }));
        $connection->expects($this->once())
            ->method('insert')
            ->will($this->throwException($this->createUniqueConstraintViolationExceptionMock()));

        $em = $this->createEntityManagerMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $this->storage->saveJob($job);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Is not possible to create new job with lock, only update is allowed
     */
    public function testShouldThrowIfTryToSaveNewEntityWithLock()
    {
        $this->storage->saveJob(
            new JobEntity(),
            function () {
            }
        );
    }

    public function testShouldLockEntityAndPassNewInstanceIntoCallback()
    {
        $job = new JobEntity();
        $job->setId(12345);
        $lockedJob = new JobEntity();

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $connection->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }));

        $em = $this->createEntityManagerMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $em->expects($this->once())
            ->method('find')
            ->with(JobEntity::class, 12345, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($lockedJob));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $resultJob = null;
        $this->storage->saveJob(
            $job,
            function (Job $job) use (&$resultJob) {
                $resultJob = $job;
            }
        );

        $this->assertSame($lockedJob, $resultJob);
    }

    public function testShouldInsertIntoUniqueTableIfJobIsUniqueAndNew()
    {
        $job = new JobEntity();
        $job->setOwnerId('owner-id');
        $job->setName('job-name');
        $job->setUnique(true);

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $connection->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }));
        $connection->expects($this->at(1))
            ->method('insert')
            ->with('unique_table', ['name' => 'owner-id']);
        $connection->expects($this->at(2))
            ->method('insert')
            ->with('unique_table', ['name' => 'job-name']);

        $em = $this->createEntityManagerMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($job));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($job));
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $this->storage->saveJob($job);
    }

    public function testShouldDeleteRecordFromUniqueTableIfJobIsUniqueAndStoppedAtIsSet()
    {
        $job = new JobEntity();
        $job->setId(12345);
        $job->setOwnerId('owner-id');
        $job->setName('job-name');
        $job->setUnique(true);
        $job->setStoppedAt(new \DateTime());

        $connection = $this->createConnectionMock();
        $connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $connection->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }));
        $connection->expects($this->at(1))
            ->method('delete')
            ->with('unique_table', ['name' => 'owner-id']);
        $connection->expects($this->at(2))
            ->method('delete')
            ->with('unique_table', ['name' => 'job-name']);

        $em = $this->createEntityManagerMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(true));
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $em->expects($this->once())
            ->method('find')
            ->with(JobEntity::class)
            ->will($this->returnValue($job));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $this->storage->saveJob(
            $job,
            function () {
            }
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UniqueConstraintViolationException
     */
    private function createUniqueConstraintViolationExceptionMock()
    {
        return $this->createMock(UniqueConstraintViolationException::class);
    }
}

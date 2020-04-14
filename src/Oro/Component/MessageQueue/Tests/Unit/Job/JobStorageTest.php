<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\UniqueJobHandler;
use Oro\Component\MessageQueue\Tests\Unit\Mock\JobEntity;

class JobStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject|UniqueJobHandler */
    private $handler;

    /** @var JobStorage */
    private $storage;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->handler = $this->createMock(UniqueJobHandler::class);

        $this->storage = new JobStorage($this->doctrine, JobEntity::class, $this->handler);
    }

    public function testShouldCreateJobObject()
    {
        $job = $this->storage->createJob();

        $this->assertEquals(JobEntity::class, get_class($job));
    }

    public function testShouldThrowIfGotUnexpectedJobInstance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected job instance of "%s", given "%s".',
            \Oro\Component\MessageQueue\Tests\Unit\Mock\JobEntity::class,
            \Oro\Component\MessageQueue\Job\Job::class
        ));

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

    public function testShouldThrowIfTryToSaveNewEntityWithLock()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Is not possible to create new job with lock, only update is allowed');

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
        $this->handler
            ->expects($this->once())
            ->method('insert')
            ->with($connection, $job);

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
        $this->handler
            ->expects($this->once())
            ->method('delete')
            ->with($connection, $job);

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

    public function testShouldCreateInitializedQueryBuilder()
    {
        $em = $this->createEntityManagerMock();
        $qb = new QueryBuilder($em);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(JobEntity::class)
            ->will($this->returnValue($em));

        $result = $this->storage->createJobQueryBuilder('e');

        self::assertEquals(
            sprintf('SELECT e FROM %s e', JobEntity::class),
            $result->getDQL()
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UniqueConstraintViolationException
     */
    private function createUniqueConstraintViolationExceptionMock()
    {
        return $this->createMock(UniqueConstraintViolationException::class);
    }
}

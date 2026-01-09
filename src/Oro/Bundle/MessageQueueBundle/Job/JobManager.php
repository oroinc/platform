<?php

namespace Oro\Bundle\MessageQueueBundle\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Component\MessageQueue\Event\AfterSaveJobEvent;
use Oro\Component\MessageQueue\Event\BeforeSaveJobEvent;
use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Job\UniqueJobHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Implements jobs insert/update database layer.
 */
class JobManager implements JobManagerInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var UniqueJobHandler */
    private $uniqueJobHandler;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        UniqueJobHandler $uniqueJobHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->uniqueJobHandler = $uniqueJobHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function saveJobWithLock(Job $job, \Closure $lockCallback): void
    {
        if (!$job->getId()) {
            throw new \LogicException('Is not possible to create new job with lock, only update is allowed');
        }

        $em = $this->getEntityManager();
        $em->getConnection()->transactional(function (Connection $connection) use ($em, $job, $lockCallback) {
            $em->getUnitOfWork()->registerManaged($job, ['id' => $job->getId()], []);

            $em->lock($job, LockMode::PESSIMISTIC_WRITE);

            $lockCallback($job);

            $this->eventDispatcher->dispatch(new BeforeSaveJobEvent($job), BeforeSaveJobEvent::EVENT_ALIAS);
            $this->updateJob($job, $em);

            if ($job->getStoppedAt()) {
                $this->uniqueJobHandler->delete($connection, $job);
            }

            $this->eventDispatcher->dispatch(new AfterSaveJobEvent($job), AfterSaveJobEvent::EVENT_ALIAS);
        });

        $em->getUnitOfWork()->clearEntityChangeSet(spl_object_hash($job));
        $em->refresh($job);
    }

    /**
     * @throws DuplicateJobException
     */
    #[\Override]
    public function saveJob(Job $job): void
    {
        $em = $this->getEntityManager();

        if (!$job->getId() && $job->isRoot()) {
            $this->uniqueJobHandler->checkRootJobOnDuplicate($em->getConnection(), $job);
        }

        $em->getConnection()->transactional(function (Connection $connection) use ($job, $em) {
            $this->eventDispatcher->dispatch(new BeforeSaveJobEvent($job), BeforeSaveJobEvent::EVENT_ALIAS);

            if ($job->getId()) {
                $this->updateJob($job, $em);
            } else {
                if ($job->isRoot()) {
                    $this->uniqueJobHandler->insert($connection, $job);
                }

                $this->insertJob($job, $em);
            }

            $this->eventDispatcher->dispatch(new AfterSaveJobEvent($job), AfterSaveJobEvent::EVENT_ALIAS);
        });

        $em->getUnitOfWork()->registerManaged($job, ['id' => $job->getId()], []);
        $em->getUnitOfWork()->clearEntityChangeSet(spl_object_hash($job));
        $em->refresh($job);
    }

    #[\Override]
    public function setCancelledStatusForChildJobs(
        Job $rootJob,
        array $statuses,
        \DateTime $stoppedAt,
        ?\DateTime $startedAt = null
    ): void {
        $em = $this->getEntityManager();
        $tableName = $em->getClassMetadata(JobEntity::class)->getTableName();

        $connection = $em->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->update($tableName)
            ->set('status', ':status')
            ->setParameter('status', Job::STATUS_CANCELLED, Types::STRING)
            ->set('stopped_at', ':stoppedAt')
            ->setParameter('stoppedAt', $stoppedAt, Types::DATETIME_MUTABLE);

        if ($startedAt) {
            $qb
                ->set('started_at', ':startedAt')
                ->setParameter('startedAt', $startedAt, Types::DATETIME_MUTABLE);
        }

        $qb
            ->where($qb->expr()->eq('root_job_id', ':rootJob'))
            ->setParameter('rootJob', $rootJob->getId(), Types::INTEGER)
            ->andWhere($qb->expr()->in('status', ':statuses'))
            ->setParameter('statuses', $statuses, Types::SIMPLE_ARRAY)
            ->execute();
    }

    public function getUniqueJobs(): array
    {
        return $this->uniqueJobHandler->list(
            $this->getEntityManager()->getConnection()
        );
    }

    private function insertJob(Job $job, EntityManager $em): void
    {
        $tableName = $em->getClassMetadata(JobEntity::class)->getTableName();

        $connection = $em->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->insert($tableName)
            ->values([
                'owner_id' => ':ownerId',
                'name' => ':name',
                'status' => ':status',
                'interrupted' => ':interrupted',
                'created_at' => ':createdAt',
                'started_at' => ':startedAt',
                'stopped_at' => ':stoppedAt',
                'last_active_at' => ':lastActiveAt',
                'root_job_id' => ':rootJob',
                'data' => ':data',
                'job_progress' => ':jobProgress',
            ]);

        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $qb->setValue('`unique`', ':unique');
        } else {
            $qb->setValue('"unique"', ':unique');
        }

        $qb
            ->setParameters([
                'ownerId' => $job->getOwnerId(),
                'name' => $job->getName(),
                'status' => $job->getStatus(),
                'unique' => (bool) $job->isUnique(),
                'interrupted' => (bool) $job->isInterrupted(),
                'createdAt' => $job->getCreatedAt(),
                'startedAt' => $job->getStartedAt(),
                'stoppedAt' => $job->getStoppedAt(),
                'lastActiveAt' => $job->getLastActiveAt(),
                'rootJob' => $job->getRootJob() ? $job->getRootJob()->getId() : null,
                'data' => $job->getData(),
                'jobProgress' => $job->getJobProgress(),
            ], [
                'ownerId' => Types::STRING,
                'name' => Types::STRING,
                'status' => Types::STRING,
                'unique' => Types::BOOLEAN,
                'interrupted' => Types::BOOLEAN,
                'createdAt' => Types::DATETIME_MUTABLE,
                'startedAt' => Types::DATETIME_MUTABLE,
                'stoppedAt' => Types::DATETIME_MUTABLE,
                'lastActiveAt' => Types::DATETIME_MUTABLE,
                'rootJob' => Types::INTEGER,
                'data' => Types::JSON,
                'jobProgress' => Types::FLOAT,
            ]);

        $qb->execute();

        $job->setId($connection->lastInsertId());
    }

    private function updateJob(Job $job, EntityManager $em): void
    {
        $tableName = $em->getClassMetadata(JobEntity::class)->getTableName();

        $connection = $em->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->update($tableName)
            ->set('owner_id', ':ownerId')
            ->set('name', ':name')
            ->set('status', ':status')
            ->set('interrupted', ':interrupted')
            ->set('created_at', ':createdAt')
            ->set('started_at', ':startedAt')
            ->set('stopped_at', ':stoppedAt')
            ->set('last_active_at', ':lastActiveAt')
            ->set('root_job_id', ':rootJob')
            ->set('data', ':data')
            ->set('job_progress', ':jobProgress');

        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $qb->set('`unique`', ':unique');
        } else {
            $qb->set('"unique"', ':unique');
        }

        $qb
            ->where($qb->expr()->eq('id', ':id'))
            ->setParameters([
                'ownerId' => $job->getOwnerId(),
                'name' => $job->getName(),
                'status' => $job->getStatus(),
                'unique' => (bool) $job->isUnique(),
                'interrupted' => (bool) $job->isInterrupted(),
                'createdAt' => $job->getCreatedAt(),
                'startedAt' => $job->getStartedAt(),
                'stoppedAt' => $job->getStoppedAt(),
                'lastActiveAt' => $job->getLastActiveAt(),
                'rootJob' => $job->getRootJob() ? $job->getRootJob()->getId() : null,
                'data' => $job->getData(),
                'jobProgress' => $job->getJobProgress(),
                'id' => $job->getId(),
            ], [
                'ownerId' => Types::STRING,
                'name' => Types::STRING,
                'status' => Types::STRING,
                'unique' => Types::BOOLEAN,
                'interrupted' => Types::BOOLEAN,
                'createdAt' => Types::DATETIME_MUTABLE,
                'startedAt' => Types::DATETIME_MUTABLE,
                'stoppedAt' => Types::DATETIME_MUTABLE,
                'lastActiveAt' => Types::DATETIME_MUTABLE,
                'rootJob' => Types::INTEGER,
                'data' => Types::JSON,
                'jobProgress' => Types::FLOAT,
                'id' => Types::INTEGER
            ]);

        $qb->execute();
    }

    private function getEntityManager(): EntityManager
    {
        return $this->doctrineHelper->getEntityManager(JobEntity::class);
    }
}

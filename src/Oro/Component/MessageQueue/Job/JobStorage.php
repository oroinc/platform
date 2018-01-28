<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;

class JobStorage
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $entityClass;

    /** @var string */
    private $uniqueTableName;

    /**
     * @param ManagerRegistry $doctrine
     * @param string          $entityClass
     * @param string          $uniqueTableName
     */
    public function __construct(ManagerRegistry $doctrine, $entityClass, $uniqueTableName)
    {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
        $this->uniqueTableName = $uniqueTableName;
    }

    /**
     * @param int $id
     *
     * @return Job|null
     */
    public function findJobById($id)
    {
        return $this->createJobQueryBuilder('job')
            ->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where('job = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $ownerId
     * @param string $jobName
     *
     * @return Job|null
     */
    public function findRootJobByOwnerIdAndJobName($ownerId, $jobName)
    {
        return $this->createJobQueryBuilder('job')
            ->where('job.ownerId = :ownerId AND job.name = :jobName')
            ->andWhere('job.rootJob is NULL')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('jobName', $jobName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds root non interrupted job by name and given statuses.
     *
     * @param string $jobName
     * @param array  $statuses
     *
     * @return Job|null
     */
    public function findRootJobByJobNameAndStatuses($jobName, array $statuses)
    {
        return $this->createJobQueryBuilder('job')
            ->where('job.rootJob is NULL and job.name = :jobName and job.status in (:statuses)')
            ->andWhere('job.interrupted != true')
            ->setParameter('jobName', $jobName)
            ->setParameter('statuses', $statuses)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $name
     * @param Job    $rootJob
     *
     * @return Job
     */
    public function findChildJobByName($name, Job $rootJob)
    {
        return $this->createJobQueryBuilder('job')
            ->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where('rootJob = :rootJob AND job.name = :name')
            ->setParameter('rootJob', $rootJob)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Job $rootJob
     *
     * @return []
     */
    public function getChildStatusesWithJobCountByRootJob(Job $rootJob)
    {
        $rawChildStatusesWithJobCount = $this->createJobQueryBuilder('job')
            ->select('COUNT(job.id) AS childCount', 'job.status')
            ->where('job.rootJob = :rootJob')
            ->groupBy('job.status')
            ->setParameter('rootJob', $rootJob)
            ->getQuery()
            ->getScalarResult();

        $childStatusesWithJobCount = [];
        foreach ($rawChildStatusesWithJobCount as $childStatusWithJobCount) {
            $childStatusesWithJobCount[$childStatusWithJobCount['status']] = $childStatusWithJobCount['childCount'];
        }

        return $childStatusesWithJobCount;
    }

    /**
     * Be warned that
     * In PGSQL function returns array of ids in DESC order, every id has integer type,
     * But in MYSQL it will be array of ids in ASC order, every id has string type
     *
     * @param Job    $rootJob
     * @param string $status
     *
     * @return array
     */
    public function getChildJobIdsByRootJobAndStatus(Job $rootJob, $status)
    {
        $qb = $this->createJobQueryBuilder('job');
        $rawChildJobIds = $qb
            ->select('job.id')
            ->where(
                $qb->expr()->andX(
                    'job.rootJob = :rootJob',
                    'job.status = :status'
                )
            )
            ->setParameters(
                new ArrayCollection(
                    [
                        new Parameter('rootJob', $rootJob),
                        new Parameter('status', $status),
                    ]
                )
            )
            ->getQuery()
            ->getScalarResult();

        return array_column($rawChildJobIds, 'id');
    }

    /**
     * @return Job
     */
    public function createJob()
    {
        return new $this->entityClass;
    }

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function createJobQueryBuilder($alias)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias);
    }

    /**
     * @param Job           $job
     * @param \Closure|null $lockCallback
     *
     * @throws DuplicateJobException
     */
    public function saveJob(Job $job, \Closure $lockCallback = null)
    {
        if (!$job instanceof $this->entityClass) {
            throw new \InvalidArgumentException(sprintf(
                'Expected job instance of "%s", given "%s".',
                $this->entityClass,
                get_class($job)
            ));
        }

        if ($lockCallback) {
            if (!$job->getId()) {
                throw new \LogicException('Is not possible to create new job with lock, only update is allowed');
            }

            $em = $this->getEntityManager(true);
            $em->getConnection()->transactional(function (Connection $connection) use ($em, $job, $lockCallback) {
                /** @var Job $job */
                $job = $em->find($this->entityClass, $job->getId(), LockMode::PESSIMISTIC_WRITE);

                $lockCallback($job);
                $em->flush($job);

                if ($job->getStoppedAt()) {
                    $connection->delete($this->uniqueTableName, ['name' => $job->getOwnerId()]);
                    if ($job->isUnique()) {
                        $connection->delete($this->uniqueTableName, ['name' => $job->getName()]);
                    }
                }
            });
        } else {
            if (!$job->getId() && $job->isRoot()) {
                // Dbal transaction is used here because Doctrine closes EntityManger any time
                // exception occurs but UniqueConstraintViolationException is expected here
                // and we should keep EntityManager in open state.
                $em = $this->getEntityManager(true);
                $em->getConnection()->transactional(function (Connection $connection) use ($job, $em) {
                    try {
                        $connection->insert($this->uniqueTableName, ['name' => $job->getOwnerId()]);
                        if ($job->isUnique()) {
                            $connection->insert($this->uniqueTableName, ['name' => $job->getName()]);
                        }
                    } catch (UniqueConstraintViolationException $e) {
                        throw new DuplicateJobException(sprintf(
                            'Duplicate job. ownerId:"%s", name:"%s"',
                            $job->getOwnerId(),
                            $job->getName()
                        ));
                    }
                    $this->flushJob($em, $job);
                });
            } else {
                $this->flushJob($this->getEntityManager(true), $job);
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Job           $job
     */
    private function flushJob(EntityManager $em, Job $job)
    {
        if (!$job->getId()) {
            $em->persist($job);
        } elseif (UnitOfWork::STATE_DETACHED === $em->getUnitOfWork()->getEntityState($job)) {
            $job = $em->merge($job);
        }

        $em->flush($job);
    }

    /**
     * @param bool $force Set TRUE if you need to get the open entity manager
     *                    even if the current entity manager is closed
     *
     * @return EntityManager
     */
    private function getEntityManager($force = false)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($this->entityClass);
        if ($force) {
            if (!$em->isOpen()) {
                $this->resetEntityManager();
                $em = $this->doctrine->getManagerForClass($this->entityClass);
            } else {
                /**
                 * ensure that the transaction is fully rolled back
                 * in case if a nested transaction is rolled back but the entity manager is not closed
                 * this may happen if the EntityManager::rollback() method is called
                 * without the call of EntityManager::close() method
                 * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/transactions-and-concurrency.html#exception-handling
                 */
                $connection = $em->getConnection();
                if ($connection->getTransactionNestingLevel() > 0 && $connection->isRollbackOnly()) {
                    while ($connection->getTransactionNestingLevel() > 0) {
                        $connection->rollBack();
                    }
                }
            }
        }

        return $em;
    }

    /**
     * Replaces the closed entity manager with new instance of entity manager.
     */
    private function resetEntityManager()
    {
        $managers = $this->doctrine->getManagers();
        foreach ($managers as $name => $manager) {
            if (!$manager->getMetadataFactory()->isTransient($this->entityClass)) {
                $this->doctrine->resetManager($name);
                break;
            }
        }
    }
}

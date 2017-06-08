<?php
namespace Oro\Component\MessageQueue\Job;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class JobStorage
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $uniqueTableName;

    /**
     * @param ManagerRegistry $doctrine
     * @param string $entityClass
     * @param string $uniqueTableName
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
     * @return Job
     */
    public function findJobById($id)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('job');

        return $qb
            ->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where('job = :id')
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    /**
     * @param Job $job
     * @param float $progress
     * @return Job
     */
    public function updateJobProgress(Job $job, $progress)
    {
        $job->setJobProgress($progress);
        $this->forceSaveJob($job);

        return $job;
    }

    /**
     * @param string $ownerId
     * @param string $jobName
     *
     * @return Job|null
     */
    public function findRootJobByOwnerIdAndJobName($ownerId, $jobName)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('job');

        return $qb
            ->where('job.ownerId = :ownerId AND job.name = :jobName')
            ->andWhere('job.rootJob is NULL')
            ->setParameters(
                [
                    'ownerId' => $ownerId,
                    'jobName' => $jobName,
                ]
            )
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds root non interrupted job by name and given statuses.
     *
     * @param string $jobName
     * @param array $statuses
     *
     * @return Job|null
     */
    public function findRootJobByJobNameAndStatuses($jobName, array $statuses)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('job');

        return $qb
            ->where('job.rootJob is NULL and job.name = :jobName and job.status in (:statuses)')
            ->andWhere('job.interrupted != true')
            ->setParameters(
                [
                    'jobName' => $jobName,
                    'statuses' => $statuses
                ]
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string  $name
     * @param Job     $rootJob
     *
     * @return Job
     */
    public function findChildJobByName($name, Job $rootJob)
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('job');

        return $qb
            ->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where('rootJob = :rootJob AND job.name = :name')
            ->setParameter('rootJob', $rootJob)
            ->setParameter('name', $name)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    /**
     * @return Job
     */
    public function createJob()
    {
        $class = $this->getEntityRepository()->getClassName();

        return new $class;
    }

    /**
     * @param Job           $job
     * @param \Closure|null $lockCallback
     *
     * @throws DuplicateJobException
     */
    public function saveJob(Job $job, \Closure $lockCallback = null)
    {
        $class = $this->getEntityRepository()->getClassName();
        if (! $job instanceof $class) {
            throw new \LogicException(sprintf(
                'Got unexpected job instance: expected: "%s", actual" "%s"',
                $class,
                get_class($job)
            ));
        }

        if ($lockCallback) {
            if (! $job->getId()) {
                throw new \LogicException('Is not possible to create new job with lock, only update is allowed');
            }

            $this->getEntityManager()->transactional(function (EntityManager $em) use ($job, $lockCallback) {
                /** @var Job $job */
                $job = $this->getEntityRepository()->find($job->getId(), LockMode::PESSIMISTIC_WRITE);

                $lockCallback($job);

                if ($job->getStoppedAt()) {
                    $this->getEntityManager()->getConnection()->delete($this->uniqueTableName, [
                        'name' => $job->getOwnerId(),
                    ]);

                    if ($job->isUnique()) {
                        $this->getEntityManager()->getConnection()->delete($this->uniqueTableName, [
                            'name' => $job->getName(),
                        ]);
                    }
                }
            });
        } else {
            if (! $job->getId() && $job->isRoot()) {
                // Dbal transaction is used here because Doctrine closes EntityManger any time
                // exception occurs but UniqueConstraintViolationException here is expected here
                // and we should keep EntityManager in open state.
                $this->getEntityManager()->getConnection()->transactional(function (Connection $connection) use ($job) {
                    try {
                        $connection->insert($this->uniqueTableName, [
                            'name' => $job->getOwnerId()
                        ]);

                        if ($job->isUnique()) {
                            $connection->insert($this->uniqueTableName, [
                                'name' => $job->getName(),
                            ]);
                        }
                    } catch (UniqueConstraintViolationException $e) {
                        throw new DuplicateJobException(sprintf(
                            'Duplicate job. ownerId:"%s", name:"%s"',
                            $job->getOwnerId(),
                            $job->getName()
                        ));
                    }
                    $this->forceSaveJob($job);
                });
            } else {
                $this->forceSaveJob($job);
            }
        }
    }

    /**
     * @param Job $job
     */
    private function forceSaveJob(Job $job)
    {
        if (! $this->getEntityManager()->isOpen()) {
            $em = $this->getEntityManager()->create(
                $this->getEntityManager()->getConnection(),
                $this->getEntityManager()->getConfiguration()
            );
            $em->merge($job);
            $em->flush($job);
        } else {
            $this->getEntityManager()->persist($job);
            $this->getEntityManager()->flush($job);
        }
    }

    /**
     * @return EntityRepository
     */
    private function getEntityRepository()
    {
        if (! $this->repository) {
            $this->repository = $this->getEntityManager()->getRepository($this->entityClass);
        }

        return $this->repository;
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        if (! $this->em) {
            $this->em = $this->doctrine->getManagerForClass($this->entityClass);
        }

        return $this->em;
    }
}

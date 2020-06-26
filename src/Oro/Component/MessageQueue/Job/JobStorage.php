<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Jobs database layer, load and save jobs responsibility
 */
class JobStorage
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $entityClass;

    /**
     * @param ManagerRegistry  $doctrine
     * @param string           $entityClass
     * @param UniqueJobHandler $uniqueJobHandler
     */
    public function __construct(ManagerRegistry $doctrine, $entityClass, UniqueJobHandler $uniqueJobHandler)
    {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
    }

    /**
     * @param int $id
     *
     * @return Job|null
     */
    public function findJobById($id)
    {
        $qb = $this->createJobQueryBuilder('job');
        $qb->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where($qb->expr()->eq('job', ':id'))
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $ownerId
     * @param string $jobName
     *
     * @return Job|null
     */
    public function findRootJobByOwnerIdAndJobName($ownerId, $jobName)
    {
        $qb = $this->createJobQueryBuilder('job');
        $qb->where($qb->expr()->isNull('job.rootJob'))
            ->andWhere($qb->expr()->eq('job.name', ':jobName'))
            ->andWhere($qb->expr()->eq('job.ownerId', ':ownerId'))
            ->setParameter('ownerId', $ownerId)
            ->setParameter('jobName', $jobName);

        return $qb->getQuery()->getOneOrNullResult();
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
        $qb = $this->createJobQueryBuilder('job');
        $qb->where($qb->expr()->isNull('job.rootJob'))
            ->andWhere($qb->expr()->eq('job.name', ':jobName'))
            ->andWhere($qb->expr()->in('job.status', ':statuses'))
            ->andWhere($qb->expr()->neq('job.interrupted', ':interrupted'))
            ->setParameter('jobName', $jobName)
            ->setParameter('statuses', $statuses)
            ->setParameter('interrupted', true)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $name
     * @param Job    $rootJob
     *
     * @return Job
     */
    public function findChildJobByName($name, Job $rootJob)
    {
        $qb = $this->createJobQueryBuilder('job');
        $qb->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where($qb->expr()->eq('job.rootJob', ':rootJob'))
            ->andWhere($qb->expr()->eq('job.name', ':name'))
            ->setParameter('rootJob', $rootJob)
            ->setParameter('name', $name);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Job $rootJob
     *
     * @return array
     */
    public function getChildStatusesWithJobCountByRootJob(Job $rootJob)
    {
        $qb = $this->createJobQueryBuilder('job');
        $rawChildStatusesWithJobCount = $qb
            ->select('COUNT(job.id) AS childCount', 'job.status')
            ->where($qb->expr()->eq('job.rootJob', ':rootJob'))
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
                    $qb->expr()->eq('job.rootJob', ':rootJob'),
                    $qb->expr()->eq('job.status', ':status')
                )
            )
            ->setParameters([
                'rootJob' => $rootJob,
                'status' => $status,
            ])
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
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($this->entityClass);

        return $em
            ->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias);
    }

    /**
     * @param Job           $job
     * @param \Closure|null $lockCallback
     */
    public function saveJob(Job $job, \Closure $lockCallback = null)
    {
        // use JobManagerInterface::saveJob or JobManagerInterface::saveJobWithLock instead
    }
}

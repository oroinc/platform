<?php
namespace Oro\Component\MessageQueue\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class JobStorage
{
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
     * @param EntityManager    $em
     * @param EntityRepository $repository
     * @param string           $uniqueTableName
     */
    public function __construct(EntityManager $em, EntityRepository $repository, $uniqueTableName)
    {
        $this->em = $em;
        $this->repository = $repository;
        $this->uniqueTableName = $uniqueTableName;
    }

    /**
     * @param int $id
     *
     * @return Job
     */
    public function findJobById($id)
    {
        $qb = $this->repository->createQueryBuilder('job');

        return $qb
            ->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where('job = :id')
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    /**
     * @param string $ownerId
     *
     * @return Job
     */
    public function findRootJobByOwnerId($ownerId)
    {
        $qb = $this->repository->createQueryBuilder('job');

        return $qb
            ->addSelect('rootJob')
            ->leftJoin('job.rootJob', 'rootJob')
            ->where('job.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    /**
     * @param string  $name
     * @param Job     $rootJob
     *
     * @return Job
     */
    public function findChildJobByName($name, Job $rootJob)
    {
        $qb = $this->repository->createQueryBuilder('job');

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
        $class = $this->repository->getClassName();

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
        $class = $this->repository->getClassName();
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

            $this->em->transactional(function (EntityManager $em) use ($job, $lockCallback) {
                /** @var Job $job */
                $job = $this->repository->find($job->getId(), LockMode::PESSIMISTIC_WRITE);

                $lockCallback($job);

                if ($job->getStoppedAt()) {
                    $em->getConnection()->delete($this->uniqueTableName, [
                        'name' => $job->getOwnerId(),
                    ]);

                    if ($job->isUnique()) {
                        $em->getConnection()->delete($this->uniqueTableName, [
                            'name' => $job->getName(),
                        ]);
                    }
                }
            });
        } else {
            if (! $job->getId() && $job->isRoot()) {
                $this->em->getConnection()->transactional(function (Connection $connection) use ($job) {
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
                        throw new DuplicateJobException();
                    }

                    $this->em->persist($job);
                    $this->em->flush();
                });
            } else {
                $this->em->persist($job);
                $this->em->flush();
            }
        }
    }
}

<?php
namespace Oro\Component\MessageQueue\Job;

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
     * @param EntityManager    $em
     * @param EntityRepository $repository
     */
    public function __construct(EntityManager $em, EntityRepository $repository)
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    /**
     * @param int $id
     *
     * @return JobEntity
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
     * @param Job           $job
     * @param \Closure|null $lockCallback
     *
     * @throws DuplicateJobException
     */
    public function saveJob(Job $job, \Closure $lockCallback = null)
    {
        if (! $job instanceof JobEntity) {
            throw new \LogicException(sprintf(
                'Got unexpected job instance: expected: "%s", actual" "%s"',
                JobEntity::class,
                get_class($job)
            ));
        }

        try {
            if ($lockCallback) {
                if (! $job->getId()) {
                    throw new \LogicException('Is not possible to create new job with lock, only update is allowed');
                }

                $this->em->transactional(function (EntityManager $em) use ($job, $lockCallback) {
                    /** @var JobEntity $job */
                    $job = $this->repository->find($job->getId(), LockMode::PESSIMISTIC_WRITE);

                    $lockCallback($job);

                    $this->updateUniqueNameField($job);
                });
            } else {
                $this->updateUniqueNameField($job);
                $this->em->persist($job);
                $this->em->flush();
            }
        } catch (UniqueConstraintViolationException $e) {
            throw new DuplicateJobException();
        }
    }

    /**
     * @param JobEntity $job
     */
    protected function updateUniqueNameField(JobEntity $job)
    {
        if (! $job->isUnique()) {
            return;
        }
        
        if (Job::STATUS_NEW === $job->getStatus()) {
            $job->setUniqueName($job->getName());
        }

        if ($job->getStoppedAt()) {
            $job->setUniqueName(null);
        }
    }
}

<?php
namespace Oro\Component\MessageQueue\Job;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;

class JobStorage
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $id
     *
     * @return EntityJob
     */
    public function findJobById($id)
    {
        $em = $this->em->getRepository(EntityJob::class)->createQueryBuilder('job');

        return $em
            ->addSelect('rootJob')
            ->innerJoin('job.rootJob', 'rootJob')
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
        if (! $job instanceof EntityJob) {
            throw new \LogicException(sprintf(
                'Got unexpected job instance: expected: "%s", actual" "%s"',
                EntityJob::class,
                get_class($job)
            ));
        }

        try {
            if ($lockCallback) {
                $this->em->transactional(function (EntityManager $em) use ($job, $lockCallback) {
                    /** @var EntityJob $job */
                    $job = $em->find(EntityJob::class, $job->getId(), LockMode::PESSIMISTIC_WRITE);

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
     * @param EntityJob $job
     */
    protected function updateUniqueNameField(EntityJob $job)
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

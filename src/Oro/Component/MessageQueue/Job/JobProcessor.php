<?php
namespace Oro\Component\MessageQueue\Job;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;

class JobProcessor
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param string $id
     *
     * @return Job
     */
    public function findJobById($id)
    {
        return $this->em->getRepository(Job::class)->find($id);
    }
    
    /**
     * @param string $name
     * @param Job    $root
     * @param bool   $unique
     *
     * @return Job
     */
    public function createJob($name, Job $root = null, $unique = false)
    {
        if ($root && ! $root->isRoot()) {
            throw new \LogicException(sprintf(
                'You can append jobs only to root job but it is not. id: "%s"',
                $root->getId()
            ));
        }

        if ($root && $unique) {
            throw new \LogicException('Can create only root unique jobs.');
        }

        $job = new Job();
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($name);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($root);

        if ($unique) {
            $job->setUniqueName($name);
        }

        try {
            $this->em->persist($job);
            $this->em->flush();

            return $job;
        } catch (UniqueConstraintViolationException $e) {
            if ($unique) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @param Job $job
     */
    public function startChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t start root jobs. id: "%s"', $job->getId()));
        }

        if ($job->getStatus() !== Job::STATUS_NEW) {
            throw new \LogicException(sprintf(
                'Can start only new jobs: id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_RUNNING);
        $job->setStartedAt(new \DateTime());

        $this->em->persist($job);
        $this->em->flush();

        // send job to analyze root status
    }

    /**
     * @param Job $job
     */
    public function stopChildJob(Job $job, $status)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t success root jobs. id: "%s"', $job->getId()));
        }

        if ($job->getStatus() !== Job::STATUS_RUNNING) {
            throw new \LogicException(sprintf(
                'Can stop only running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $validStopStatuses = [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED];
        if (! in_array($status, $validStopStatuses)) {
            throw new \LogicException(sprintf(
                'This status is not valid stop status. status: "%s", valid: [%s]',
                $job->getStatus(),
                implode(', ', $validStopStatuses)
            ));
        }

        $job->setStatus($status);
        $job->setStoppedAt(new \DateTime());

        $this->em->persist($job);
        $this->em->flush();

        // send job to analyze root status
    }

    /**
     * @param Job  $job
     * @param bool $force
     */
    public function interruptRootJob(Job $job, $force)
    {
        if (! $job->isRoot()) {
            throw new \LogicException(sprintf('Can interrupt only root jobs. id: "%s"', $job->getId()));
        }

        $this->em->transactional(function (EntityManager $em) use ($job, $force) {
            /** @var Job $job */
            $job = $em->find(Job::class, $job->getId(), LockMode::PESSIMISTIC_WRITE);

            $job->setInterrupted(true);

            if ($force) {
                $job->setUniqueName(null);
                $job->setStoppedAt(new \DateTime());
            }
        });
    }
}

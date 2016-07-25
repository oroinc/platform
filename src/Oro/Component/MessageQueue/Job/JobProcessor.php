<?php
namespace Oro\Component\MessageQueue\Job;

class JobProcessor
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param JobStorage $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    /**
     * @param string $id
     *
     * @return Job
     */
    public function findJobById($id)
    {
        return $this->jobStorage->findJobById($id);
    }
    
    /**
     * @param string $name
     * @param Job    $root
     * @param bool   $unique
     *
     * @return Job
     *
     * @throws DuplicateJobException
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
        $job->setUnique((bool) $unique);

        try {
            $this->jobStorage->saveJob($job);

            return $job;
        } catch (DuplicateJobException $e) {
            if ($unique) {
                return;
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

        $this->jobStorage->saveJob($job);

        // send job to analyze root status
    }

    /**
     * @param Job $job
     */
    public function stopChildJob(Job $job, $status)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t stop root jobs. id: "%s"', $job->getId()));
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
                'This status is not valid stop status. id: "%s", status: "%s", valid: [%s]',
                $job->getId(),
                $status,
                implode(', ', $validStopStatuses)
            ));
        }

        $job->setStatus($status);
        $job->setStoppedAt(new \DateTime());

        $this->jobStorage->saveJob($job);

        // send job to analyze root status
    }

    /**
     * @param Job  $job
     * @param bool $force
     */
    public function interruptRootJob(Job $job, $force = false)
    {
        if (! $job->isRoot()) {
            throw new \LogicException(sprintf('Can interrupt only root jobs. id: "%s"', $job->getId()));
        }

        if ($job->isInterrupted()) {
            return;
        }

        $this->jobStorage->saveJob($job, function (Job $job) use ($force) {
            if ($job->isInterrupted()) {
                return;
            }

            $job->setInterrupted(true);

            if ($force) {
                $job->setStoppedAt(new \DateTime());
            }
        });
    }
}

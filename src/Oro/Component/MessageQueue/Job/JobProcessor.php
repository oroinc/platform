<?php
namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class JobProcessor
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @param JobStorage               $jobStorage
     * @param MessageProducerInterface $producer
     */
    public function __construct(JobStorage $jobStorage, MessageProducerInterface $producer)
    {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
    }

    /**
     * @return JobRunner
     */
    public function createJobRunner()
    {
        return new JobRunner($this);
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
     * @param string $ownerId
     * @param string $name
     * @param bool   $unique
     *
     * @return Job
     */
    public function findOrCreateRootJob($ownerId, $name, $unique = false)
    {
        if (! $ownerId) {
            throw new \LogicException('OwnerId must be not empty');
        }

        if (! $name) {
            throw new \LogicException('Name must be not empty');
        }

        $job = $this->jobStorage->createJob();
        $job->setOwnerId($ownerId);
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($name);
        $job->setCreatedAt(new \DateTime());
        $job->setStartedAt(new \DateTime());
        $job->setUnique((bool) $unique);

        try {
            $this->jobStorage->saveJob($job);

            return $job;
        } catch (DuplicateJobException $e) {
        }

        return $this->jobStorage->findRootJobByOwnerId($ownerId);
    }

    /**
     * @param string $name
     * @param Job $rootJob
     *
     * @return Job
     */
    public function findOrCreateChildJob($name, Job $rootJob)
    {
        if (! $name) {
            throw new \LogicException('Name must be not empty');
        }

        $job = $this->jobStorage->findChildJobByName($name, $rootJob);

        if ($job) {
            return $job;
        }
        
        $job = $this->jobStorage->createJob();
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($name);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($rootJob);

        $this->jobStorage->saveJob($job);

        $this->producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, [
            'id' => $job->getId()
        ]);

        return $job;
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

        $this->producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, [
            'id' => $job->getId()
        ]);
    }

    /**
     * @param Job $job
     */
    public function successChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t success root jobs. id: "%s"', $job->getId()));
        }

        if ($job->getStatus() !== Job::STATUS_RUNNING) {
            throw new \LogicException(sprintf(
                'Can success only running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setStoppedAt(new \DateTime());

        $this->jobStorage->saveJob($job);

        $this->producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, [
            'id' => $job->getId()
        ]);
    }

    /**
     * @param Job $job
     */
    public function failChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t fail root jobs. id: "%s"', $job->getId()));
        }

        if ($job->getStatus() !== Job::STATUS_RUNNING) {
            throw new \LogicException(sprintf(
                'Can fail only running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_FAILED);
        $job->setStoppedAt(new \DateTime());

        $this->jobStorage->saveJob($job);

        $this->producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, [
            'id' => $job->getId()
        ]);
    }

    /**
     * @param Job $job
     */
    public function cancelChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t cancel root jobs. id: "%s"', $job->getId()));
        }

        if (! in_array($job->getStatus(), [Job::STATUS_NEW, Job::STATUS_RUNNING])) {
            throw new \LogicException(sprintf(
                'Can cancel only new or running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_CANCELLED);
        $job->setStoppedAt($stoppedAt = new \DateTime());

        if (! $job->getStartedAt()) {
            $job->setStartedAt($stoppedAt);
        }

        $this->jobStorage->saveJob($job);

        $this->producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, [
            'id' => $job->getId()
        ]);
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

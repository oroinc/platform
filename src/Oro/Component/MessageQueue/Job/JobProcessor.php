<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\Persistence\ObjectRepository;
use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;
use Oro\Component\MessageQueue\Provider\NullJobConfigurationProvider;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * JobProcessor is a main class responsible for processing jobs, shifting it's responsibilities to other classes
 * is quite difficult and would make it less readable.
 */
class JobProcessor
{
    /** @var JobConfigurationProviderInterface */
    private $jobConfigurationProvider;

    /** @var JobManagerInterface */
    private $jobManager;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $entityClass;

    /**
     * @param JobManagerInterface $jobManager
     * @param ManagerRegistry $doctrine
     * @param string $entityClass
     */
    public function __construct(JobManagerInterface $jobManager, ManagerRegistry $doctrine, string $entityClass)
    {
        $this->jobManager = $jobManager;
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
    }

    /**
     * @param JobConfigurationProviderInterface $jobConfigurationProvider
     * @return self
     */
    public function setJobConfigurationProvider(JobConfigurationProviderInterface $jobConfigurationProvider): self
    {
        $this->jobConfigurationProvider = $jobConfigurationProvider;

        return $this;
    }

    /**
     * @return JobConfigurationProviderInterface
     */
    public function getJobConfigurationProvider(): JobConfigurationProviderInterface
    {
        if (!$this->jobConfigurationProvider) {
            return new NullJobConfigurationProvider();
        }
        return $this->jobConfigurationProvider;
    }

    /**
     * @param int $id
     *
     * @return Job|null
     */
    public function findJobById(int $id): ?Job
    {
        return $this->getJobRepository()->findJobById($id);
    }

    /**
     * Finds root non interrupted job by name and given statuses.
     *
     * @param string $jobName
     * @param array $statuses
     *
     * @return Job|null
     */
    public function findRootJobByJobNameAndStatuses(string $jobName, array $statuses): ?Job
    {
        return $this->getJobRepository()->findRootJobByJobNameAndStatuses($jobName, $statuses);
    }

    /**
     * @param string $ownerId
     * @param string $jobName
     * @param bool $unique
     *
     * @return Job|null
     */
    public function findOrCreateRootJob(string $ownerId, string $jobName, bool $unique = false): ?Job
    {
        if (!$ownerId) {
            throw new \LogicException('OwnerId must not be empty');
        }

        if (!$jobName) {
            throw new \LogicException('Job name must not be empty');
        }

        $job = $this->getJobRepository()->findRootJobByOwnerIdAndJobName($ownerId, $jobName);
        if ($job) {
            return $job;
        }

        $job = $this->getJobRepository()->createJob();
        $job->setOwnerId($ownerId);
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($jobName);
        $job->setCreatedAt(new \DateTime());
        $job->setLastActiveAt(new \DateTime());
        $job->setStartedAt(new \DateTime());
        $job->setJobProgress(0);
        $job->setUnique((bool) $unique);

        return $this->saveJobAndStaleDuplicateIfQualifies($job);
    }

    /**
     * @param string $jobName
     * @param Job $rootJob
     *
     * @return Job
     */
    public function findOrCreateChildJob(string $jobName, Job $rootJob): ?Job
    {
        if (!$jobName) {
            throw new \LogicException('Job name must not be empty');
        }

        $job = $this->getJobRepository()->findChildJobByName($jobName, $rootJob);

        if ($job) {
            return $job;
        }

        $job = $this->getJobRepository()->createJob();
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($jobName);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($rootJob);
        $rootJob->addChildJob($job);
        $job->setJobProgress(0);
        $this->jobManager->saveJob($job);

        return $job;
    }

    /**
     * @param Job $job
     */
    public function startChildJob(Job $job): void
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t start root jobs. id: "%s"', $job->getId()));
        }

        if (!in_array($job->getStatus(), $this->getNotStartedJobStatuses(), true)) {
            throw new \LogicException(sprintf(
                'Can start only new jobs: id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_RUNNING);
        $job->setStartedAt(new \DateTime());

        $this->jobManager->saveJob($job);
    }

    /**
     * @param Job $job
     */
    public function successChildJob(Job $job): void
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t success root jobs. id: "%s"', $job->getId()));
        }

        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setJobProgress(1);
        $job->setStoppedAt(new \DateTime());
        $this->jobManager->saveJob($job);
    }

    /**
     * @param Job $job
     */
    public function failChildJob(Job $job): void
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t fail root jobs. id: "%s"', $job->getId()));
        }

        $job->setStatus(Job::STATUS_FAILED);
        $job->setStoppedAt(new \DateTime());

        $this->jobManager->saveJob($job);
    }

    /**
     * @param Job $job
     */
    public function failAndRedeliveryChildJob(Job $job): void
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t fail root jobs. id: "%s"', $job->getId()));
        }

        $job->setStatus(Job::STATUS_FAILED_REDELIVERED);
        $this->jobManager->saveJob($job);
    }

    /**
     * @param Job $job
     */
    public function interruptRootJob(Job $job): void
    {
        if (!$job->isRoot()) {
            throw new \LogicException(sprintf('Can interrupt only root jobs. id: "%s"', $job->getId()));
        }

        if ($job->isInterrupted()) {
            return;
        }

        $this->jobManager->saveJobWithLock($job, function (Job $job) {
            if ($job->isInterrupted()) {
                return;
            }

            $stoppedAt = new \DateTime();
            $job->setInterrupted(true);
            $job->setStoppedAt($stoppedAt);
            $job->setLastActiveAt($stoppedAt);
            $job->setStatus(Job::STATUS_CANCELLED);

            // Cancel only jobs that should be processed, to make in order to save history for already processed jobs.
            $this->jobManager->setCancelledStatusForChildJobs($job, [Job::STATUS_NEW], $stoppedAt, $stoppedAt);
            $this->jobManager->setCancelledStatusForChildJobs($job, [Job::STATUS_FAILED_REDELIVERED], $stoppedAt);
        });
    }

    /**
     * Finds root non interrupted and non stale job by name and given statuses.
     *
     * @param string $jobName
     * @param array $statuses
     *
     * @return Job|null
     */
    public function findNotStaleRootJobyJobNameAndStatuses(string $jobName, array $statuses): ?Job
    {
        $currentRootJob = $this->findRootJobByJobNameAndStatuses($jobName, $statuses);
        if ($currentRootJob) {
            if ($this->isJobStale($currentRootJob)) {
                $this->staleRootJobAndChildren($currentRootJob);
            } else {
                return $currentRootJob;
            }
        }

        return null;
    }

    /**
     * @param Job $rootJob
     */
    private function staleRootJobAndChildren(Job $rootJob): void
    {
        if (!$rootJob->isRoot()) {
            throw new \LogicException(sprintf('Can\'t stale child jobs. id: "%s"', $rootJob->getId()));
        }

        $this->jobManager->saveJobWithLock($rootJob, function (Job $rootJob) {
            $rootJob->setStatus(Job::STATUS_STALE);
            $rootJob->setStoppedAt(new \DateTime());

            foreach ($rootJob->getChildJobs() as $childJob) {
                if (in_array($childJob->getStatus(), $this->getActiveJobStatuses(), true)) {
                    $childJob->setStatus(Job::STATUS_STALE);
                    $childJob->setStoppedAt(new \DateTime());
                    $this->jobManager->saveJob($childJob);
                }
            }
        });
    }

    /**
     * @param Job $job
     * @return Job|null
     */
    private function saveJobAndStaleDuplicateIfQualifies(Job $job): ?Job
    {
        try {
            $this->jobManager->saveJob($job);

            return $job;
        } catch (DuplicateJobException $e) {
            $currentRootJob = $this->findRootJobByJobNameAndStatuses($job->getName(), $this->getActiveJobStatuses());
            if ($currentRootJob && $this->isJobStale($currentRootJob)) {
                $this->staleRootJobAndChildren($currentRootJob);

                return $this->saveJobAndStaleDuplicateIfQualifies($job);
            }
        }
        return null;
    }

    /**
     * @param Job $job
     * @return bool
     */
    private function isJobStale(Job $job): bool
    {
        if ($job->getStatus() === Job::STATUS_STALE) {
            return true;
        }

        if ($this->hasNotStartedChild($job)) {
            return false;
        }

        $timeBeforeStale = $this->getJobConfigurationProvider()->getTimeBeforeStaleForJobName($job->getName());
        if ($timeBeforeStale !== null && $timeBeforeStale !== -1) {
            return $job->getLastActiveAt() <= new \DateTime('- ' . $timeBeforeStale. ' seconds');
        }

        return false;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function hasNotStartedChild(Job $job): bool
    {
        foreach ($job->getChildJobs() as $childJob) {
            if (Job::STATUS_NEW === $childJob->getStatus()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getNotStartedJobStatuses(): array
    {
        return [Job::STATUS_NEW, Job::STATUS_FAILED_REDELIVERED];
    }

    /**
     * @return string[]
     */
    private function getActiveJobStatuses(): array
    {
        return [Job::STATUS_NEW, Job::STATUS_RUNNING, Job::STATUS_FAILED_REDELIVERED];
    }

    /**
     * @return JobRepositoryInterface|ObjectRepository
     */
    private function getJobRepository(): JobRepositoryInterface
    {
        return $this->doctrine->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}

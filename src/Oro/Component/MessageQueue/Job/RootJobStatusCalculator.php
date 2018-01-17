<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

class RootJobStatusCalculator
{
    /** @var string[] */
    private static $finishStatuses = [
        Job::STATUS_SUCCESS,
        Job::STATUS_FAILED,
        Job::STATUS_STALE
    ];

    /** @var string[] */
    private static $stopStatuses = [
        Job::STATUS_SUCCESS,
        Job::STATUS_FAILED,
        Job::STATUS_CANCELLED,
        Job::STATUS_STALE
    ];

    /** @var JobStorage */
    private $jobStorage;

    /**
     * @param JobStorage $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    /**
     * @param Job  $job
     * @param bool $calculateProgress
     *
     * @return bool true if root job was stopped
     */
    public function calculate(Job $job, $calculateProgress = false)
    {
        $rootJob = $this->getRootJob($job);
        if ($this->isJobStopped($rootJob)) {
            return false;
        }

        $rootStopped = false;
        $childJobs = $this->getChildJobs($rootJob);
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use (
            &$rootStopped,
            $calculateProgress,
            $childJobs
        ) {
            if (!$this->isJobStopped($rootJob)) {
                $rootStopped = $this->updateRootJob($rootJob, $childJobs, $calculateProgress);
            }
        });

        return $rootStopped;
    }

    /**
     * @param Job   $rootJob
     * @param Job[] $childJobs
     * @param bool  $calculateProgress
     *
     * @return bool
     */
    private function updateRootJob(Job $rootJob, array $childJobs, $calculateProgress)
    {
        $rootStopped = false;
        $rootJob->setLastActiveAt(new \DateTime());

        $rootJob->setStatus($this->calculateRootJobStatus($childJobs));
        if ($this->isJobStopped($rootJob)) {
            $rootStopped = true;
            $calculateProgress = true;
            if (!$rootJob->getStoppedAt()) {
                $rootJob->setStoppedAt(new \DateTime());
            }
        }

        if ($calculateProgress) {
            $progress = $this->calculateRootJobProgress($childJobs);
            if ($rootJob->getJobProgress() !== $progress) {
                $rootJob->setJobProgress($progress);
            }
        }

        return $rootStopped;
    }

    /**
     * @param Job[] $jobs
     *
     * @return float
     */
    private function calculateRootJobProgress(array $jobs)
    {
        $numberOfChildren = count($jobs);
        if (0 === $numberOfChildren) {
            return 0;
        }

        $processed = 0;
        foreach ($jobs as $job) {
            if ($this->isJobFinished($job)) {
                $processed++;
            }
        }

        return round($processed / $numberOfChildren, 4);
    }

    /**
     * @param Job[] $jobs
     *
     * @return string
     */
    private function calculateRootJobStatus(array $jobs)
    {
        $new = 0;
        $running = 0;
        $cancelled = 0;
        $failed = 0;
        $success = 0;
        $failedRedelivery = 0;

        foreach ($jobs as $job) {
            switch ($job->getStatus()) {
                case Job::STATUS_NEW:
                    $new++;
                    break;
                case Job::STATUS_RUNNING:
                case Job::STATUS_STALE:
                    $running++;
                    break;
                case Job::STATUS_CANCELLED:
                    $cancelled++;
                    break;
                case Job::STATUS_FAILED:
                    $failed++;
                    break;
                case Job::STATUS_FAILED_REDELIVERED:
                    $failedRedelivery++;
                    break;
                case Job::STATUS_SUCCESS:
                    $success++;
                    break;
                default:
                    throw new \LogicException(sprintf(
                        'Got unsupported job status: id: "%s" status: "%s"',
                        $job->getId(),
                        $job->getStatus()
                    ));
            }
        }

        return $this->getRootJobStatus($new, $running, $cancelled, $failed, $success, $failedRedelivery);
    }

    /**
     * @param int $new
     * @param int $running
     * @param int $cancelled
     * @param int $failed
     * @param int $success
     * @param int $failedRedelivery
     *
     * @return string
     */
    private function getRootJobStatus($new, $running, $cancelled, $failed, $success, $failedRedelivery)
    {
        $status = Job::STATUS_NEW;
        if (!$new && !$running && !$failedRedelivery) {
            if ($cancelled) {
                $status = Job::STATUS_CANCELLED;
            } elseif ($failed) {
                $status = Job::STATUS_FAILED;
            } else {
                $status = Job::STATUS_SUCCESS;
            }
        } elseif ($running || $cancelled || $failed || $success || $failedRedelivery) {
            $status = Job::STATUS_RUNNING;
        }

        return $status;
    }

    /**
     * @param Job $job
     *
     * @return Job
     */
    private function getRootJob(Job $job)
    {
        if ($job->isRoot()) {
            return $job;
        }

        return $job->getRootJob();
    }

    /**
     * @param Job $rootJob
     *
     * @return Job[]
     */
    private function getChildJobs(Job $rootJob)
    {
        $childJobs = $rootJob->getChildJobs();
        if ($childJobs instanceof PersistentCollection) {
            if ($childJobs->isInitialized()) {
                $childJobs = $childJobs->toArray();
            } else {
                // using ScalarHydrator instead of ObjectHydrator gives a slight performance improvement,
                // especially when there are a lot of child jobs
                $childJobs = [];
                $rows = $this->jobStorage->createJobQueryBuilder('e')
                    ->select('e.id, e.status')
                    ->where('e.rootJob = :rootJob')
                    ->setParameter('rootJob', $rootJob)
                    ->getQuery()
                    ->getScalarResult();
                foreach ($rows as $row) {
                    $job = $this->jobStorage->createJob();
                    $job->setId($row['id']);
                    $job->setStatus($row['status']);
                    $childJobs[] = $job;
                }
            }
        } elseif ($childJobs instanceof Collection) {
            $childJobs = $childJobs->toArray();
        }

        return $childJobs;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isJobStopped(Job $job)
    {
        return in_array($job->getStatus(), self::$stopStatuses, true);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isJobFinished(Job $job)
    {
        return in_array($job->getStatus(), self::$finishStatuses, true);
    }
}

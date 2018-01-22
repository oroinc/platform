<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\StatusCalculator\AbstractStatusCalculator;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

class RootJobStatusCalculator
{
    /** @var JobStorage */
    private $jobStorage;

    /** @var JobStatusChecker */
    private $jobStatusChecker;

    /** @var StatusCalculatorResolver */
    private $statusCalculatorResolver;

    /**
     * @param JobStorage               $jobStorage
     * @param JobStatusChecker         $jobStatusChecker
     * @param StatusCalculatorResolver $statusCalculatorResolver
     */
    public function __construct(
        JobStorage $jobStorage,
        JobStatusChecker $jobStatusChecker,
        StatusCalculatorResolver $statusCalculatorResolver
    ) {
        $this->jobStorage = $jobStorage;
        $this->jobStatusChecker = $jobStatusChecker;
        $this->statusCalculatorResolver = $statusCalculatorResolver;
    }

    /**
     * @param Job  $job
     * @param bool $calculateProgress
     *
     * @return bool true in the case when the status of "root" job changed on stop status by this method
     */
    public function calculate(Job $job, $calculateProgress = false)
    {
        $rootJob = $this->getRootJob($job);
        if ($this->jobStatusChecker->isJobStopped($rootJob)) {
            return false;
        }

        $rootStopped = false;
        $statusAndProgressCalculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use (
            &$rootStopped,
            $calculateProgress,
            $statusAndProgressCalculator
        ) {
            if (!$this->jobStatusChecker->isJobStopped($rootJob)) {
                $rootStopped = $this->updateRootJob($rootJob, $statusAndProgressCalculator, $calculateProgress);
            }
        });

        $statusAndProgressCalculator->clean();

        return $rootStopped;
    }

    /**
     * @param Job                      $rootJob
     * @param AbstractStatusCalculator $statusAndProgressCalculator
     * @param bool                     $calculateProgress
     *
     * @return bool
     */
    private function updateRootJob(
        Job $rootJob,
        AbstractStatusCalculator $statusAndProgressCalculator,
        $calculateProgress
    ) {
        $rootStopped = false;
        $rootJob->setLastActiveAt(new \DateTime());

        $rootJobStatus = $statusAndProgressCalculator->calculateRootJobStatus();
        $rootJob->setStatus($rootJobStatus);
        if ($this->jobStatusChecker->isJobStopped($rootJob)) {
            $rootStopped = true;
            $calculateProgress = true;
            if (!$rootJob->getStoppedAt()) {
                $rootJob->setStoppedAt(new \DateTime());
            }
        }

        if ($calculateProgress) {
            $progress = $statusAndProgressCalculator->calculateRootJobProgress();
            if ($rootJob->getJobProgress() !== $progress) {
                $rootJob->setJobProgress($progress);
            }
        }

        return $rootStopped;
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
}

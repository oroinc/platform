<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Exception\JobNotFoundException;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Exception\StaleJobRuntimeException;
use Oro\Component\MessageQueue\Job\Extension\ExtensionInterface;

/**
 * Provides possibility to run unique or delayed jobs
 */
class JobRunner
{
    /** @var JobProcessor */
    private $jobProcessor;

    /** @var ExtensionInterface */
    private $jobExtension;

    /** @var Job */
    private $rootJob;

    /**
     * @param JobProcessor       $jobProcessor
     * @param ExtensionInterface $jobExtension
     * @param Job                $rootJob
     */
    public function __construct(JobProcessor $jobProcessor, ExtensionInterface $jobExtension, Job $rootJob = null)
    {
        $this->jobProcessor = $jobProcessor;
        $this->jobExtension = $jobExtension;
        $this->rootJob = $rootJob;
    }

    /**
     * @param string $ownerId
     * @param string $name
     * @param \Closure $runCallback
     *
     * @return mixed
     */
    public function runUnique($ownerId, $name, \Closure $runCallback)
    {
        $rootJob = $this->jobProcessor->findOrCreateRootJob($ownerId, $name, true);
        if (!$rootJob) {
            return null;
        }
        $this->throwIfJobIsStale($rootJob);

        $childJob = $this->jobProcessor->findOrCreateChildJob($name, $rootJob);

        if ($rootJob->isInterrupted()) {
            $this->jobProcessor->cancelAllActiveChildJobs($rootJob);
            $this->jobExtension->onCancel($childJob);

            return null;
        }

        if ($this->isReadyForStart($childJob)) {
            $this->jobProcessor->startChildJob($childJob);
        }

        $this->jobExtension->onPreRunUnique($childJob);

        $result = $this->callbackResult($runCallback, $childJob);

        if ($this->isReadyForStop($childJob)) {
            $result
                ? $this->jobProcessor->successChildJob($childJob)
                : $this->jobProcessor->failChildJob($childJob);
        }

        $this->jobExtension->onPostRunUnique($childJob, $result);

        return $result;
    }

    /**
     * @param string   $name
     * @param \Closure $startCallback
     *
     * @return mixed
     */
    public function createDelayed($name, \Closure $startCallback)
    {
        $childJob = $this->jobProcessor->findOrCreateChildJob($name, $this->rootJob);

        $this->jobExtension->onPreCreateDelayed($childJob);

        $jobRunner = $this->getJobRunnerForChildJob($this->rootJob);

        try {
            $createResult = call_user_func($startCallback, $jobRunner, $childJob);
        } catch (\Throwable $e) {
            $this->jobProcessor->failChildJob($childJob);
            $this->jobExtension->onError($childJob);

            throw new JobRuntimeException(sprintf(
                'An error occurred while created job, id: %d',
                $childJob->getId()
            ), 0, $e);
        }

        $this->jobExtension->onPostCreateDelayed($childJob, $createResult);

        return $createResult;
    }

    /**
     * @param string   $jobId
     * @param \Closure $runCallback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function runDelayed($jobId, \Closure $runCallback)
    {
        $job = $this->jobProcessor->findJobById($jobId);
        if (! $job) {
            throw JobNotFoundException::create($jobId);
        }
        $this->throwIfJobIsStale($job);

        if ($job->getRootJob()->isInterrupted()) {
            $this->jobProcessor->cancelAllActiveChildJobs($job->getRootJob());
            $this->jobExtension->onCancel($job);

            return null;
        }

        if ($this->isReadyForStart($job)) {
            $this->jobProcessor->startChildJob($job);
        }

        $this->jobExtension->onPreRunDelayed($job);

        $result = $this->callbackResult($runCallback, $job);

        if ($this->isReadyForStop($job)) {
            $result
                ? $this->jobProcessor->successChildJob($job)
                : $this->jobProcessor->failChildJob($job);
        }

        $this->jobExtension->onPostRunDelayed($job, $result);

        return $result;
    }

    /**
     * @param Job $job
     */
    private function throwIfJobIsStale($job)
    {
        if ($job->getStatus() === Job::STATUS_STALE) {
            throw new StaleJobRuntimeException(sprintf(
                'Cannot run jobs in status stale, id: "%s"',
                $job->getId()
            ));
        }
    }

    /**
     * @param Job $rootJob
     *
     * @return JobRunner
     */
    private function getJobRunnerForChildJob(Job $rootJob)
    {
        return new JobRunner($this->jobProcessor, $this->jobExtension, $rootJob);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isReadyForStart(Job $job)
    {
        return !$job->getStartedAt() || $job->getStatus() === Job::STATUS_FAILED_REDELIVERED;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isReadyForStop(Job $job)
    {
        return !$job->getStoppedAt() || $job->getStatus() === Job::STATUS_FAILED_REDELIVERED;
    }

    /**
     * @param \Closure $runCallback
     * @param Job $job
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function callbackResult($runCallback, $job)
    {
        $jobRunner = $this->getJobRunnerForChildJob($job->getRootJob());
        try {
            $result = call_user_func($runCallback, $jobRunner, $job);
        } catch (\Throwable $e) {
            $this->jobProcessor->failAndRedeliveryChildJob($job);
            $this->jobExtension->onError($job);

            throw new JobRuntimeException(sprintf(
                'An error occurred while running job, id: %d',
                $job->getId()
            ), 0, $e);
        }

        return $result;
    }
}

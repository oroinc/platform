<?php

namespace Oro\Component\MessageQueue\Job;

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

        $childJob = $this->jobProcessor->findOrCreateChildJob($name, $rootJob);

        if ($rootJob->isInterrupted()) {
            $this->jobProcessor->cancelAllActiveChildJobs($rootJob);

            return null;
        }

        $this->jobExtension->onPreRunUnique($childJob);

        if ($this->isReadyForStart($childJob)) {
            $this->jobProcessor->startChildJob($childJob);
        }

        $result = $this->callbackResult($runCallback, $childJob);

        if (!$childJob->getStoppedAt()) {
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
        $createResult = call_user_func($startCallback, $jobRunner, $childJob);

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
            throw new \LogicException(sprintf('Job was not found. id: "%s"', $jobId));
        }

        if ($job->getRootJob()->isInterrupted()) {
            if (! $job->getStoppedAt()) {
                $this->jobProcessor->cancelChildJob($job);
            }

            return null;
        }

        $this->jobExtension->onPreRunDelayed($job);

        if ($this->isReadyForStart($job)) {
            $this->jobProcessor->startChildJob($job);
        }

        $result = $this->callbackResult($runCallback, $job);

        if (! $job->getStoppedAt()) {
            $result
                ? $this->jobProcessor->successChildJob($job)
                : $this->jobProcessor->failChildJob($job);
        }

        $this->jobExtension->onPostRunDelayed($job, $result);

        return $result;
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
        } catch (\Exception $e) {
            $this->jobProcessor->failAndRedeliveryChildJob($job);

            throw $e;
        }

        return $result;
    }
}

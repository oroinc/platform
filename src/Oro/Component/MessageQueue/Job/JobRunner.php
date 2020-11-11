<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Exception\JobNotFoundException;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Exception\StaleJobRuntimeException;
use Oro\Component\MessageQueue\Job\Extension\ExtensionInterface;

/**
 * Provides possibility to run unique or delayed jobs.
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
     * @param Job|null           $rootJob
     */
    public function __construct(JobProcessor $jobProcessor, ExtensionInterface $jobExtension, Job $rootJob = null)
    {
        $this->jobProcessor = $jobProcessor;
        $this->jobExtension = $jobExtension;
        $this->rootJob = $rootJob;
    }

    /**
     * Creates a root job and runs the $runCallback.
     * It does not allow another job with the same name to run simultaneously.
     *
     * @param string   $ownerId
     * @param string   $name
     * @param \Closure $runCallback
     *
     * @return mixed A value returned by the $runCallback or NULL if this closure cannot be run
     */
    public function runUnique($ownerId, $name, \Closure $runCallback)
    {
        $rootJob = $this->jobProcessor->findOrCreateRootJob($ownerId, $name, true);
        if (!$rootJob) {
            return null;
        }

        $this->throwIfJobIsStale($rootJob);

        $childJob = $this->jobProcessor->findOrCreateChildJob($name, $rootJob);

        if ($childJob->getStatus() === Job::STATUS_CANCELLED) {
            $this->jobExtension->onCancel($childJob);

            return null;
        }

        if ($this->isReadyForStart($childJob)) {
            $this->jobProcessor->startChildJob($childJob);
        }

        $this->jobExtension->onPreRunUnique($childJob);

        $result = $this->callbackResult($runCallback, $childJob);
        $result
            ? $this->jobProcessor->successChildJob($childJob)
            : $this->jobProcessor->failChildJob($childJob);

        $this->jobExtension->onPostRunUnique($childJob, $result);

        return $result;
    }

    /**
     * Creates a delayed sub-job which runs asynchronously (sending its own message).
     * It can only run inside another job.
     *
     * It is a common approach to create a delayed job simultaneously with a queue message that contains
     * information about the job. In this case, after receiving the message, the subscribed message processor
     * can run and perform a delayed job by running the {@see runDelayed()} method with the job data.
     *
     * @param string   $name
     * @param \Closure $startCallback
     *
     * @return mixed A value returned by the $startCallback
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
     * Runs a delayed sub-job.
     * This method is used inside a processor for a message which was sent with {@see createDelayed()} method.
     *
     * The $runCallback closure usually returns true or false, the job status depends on the returned value.
     * See {@link https://doc.oroinc.com/backend/mq/message-queue-jobs/#jobs-statuses} for the details.
     *
     * To reuse the existing processor logic in the scope of job, it may be decorated with
     * {@see \Oro\Component\MessageQueue\Job\DelayedJobRunnerDecoratingProcessor} which will execute runDelayed(),
     * pass the control to the given processor and then handle the result in the format applicable for runDelayed().
     *
     * @param string   $jobId
     * @param \Closure $runCallback
     *
     * @return mixed A value returned by the $runCallback
     */
    public function runDelayed($jobId, \Closure $runCallback)
    {
        $job = $this->jobProcessor->findJobById($jobId);
        if (!$job) {
            throw JobNotFoundException::create($jobId);
        }

        $this->throwIfJobIsStale($job);

        if ($job->getStatus() === Job::STATUS_CANCELLED) {
            $this->jobExtension->onCancel($job);

            return null;
        }

        if ($this->isReadyForStart($job)) {
            $this->jobProcessor->startChildJob($job);
        }

        $this->jobExtension->onPreRunDelayed($job);

        $result = $this->callbackResult($runCallback, $job);
        $result
            ? $this->jobProcessor->successChildJob($job)
            : $this->jobProcessor->failChildJob($job);

        $this->jobExtension->onPostRunDelayed($job, $result);

        return $result;
    }

    /**
     * Creates and return a new instance of JobRunner that can be used in sub-jobs.
     *
     * @param Job $rootJob
     *
     * @return JobRunner
     */
    public function getJobRunnerForChildJob(Job $rootJob)
    {
        return new JobRunner($this->jobProcessor, $this->jobExtension, $rootJob);
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
     * @param Job      $job
     *
     * @return mixed
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

<?php

namespace Oro\Component\MessageQueue\Job;

use Symfony\Component\Filesystem\LockHandler;

class JobRunner
{
    /**
     * @var JobProcessor
     */
    private $jobProcessor;

    /**
     * @var Job
     */
    private $rootJob;

    /**
     * @param JobProcessor $jobProcessor
     * @param Job          $rootJob
     */
    public function __construct(JobProcessor $jobProcessor, Job $rootJob = null)
    {
        $this->jobProcessor = $jobProcessor;
        $this->rootJob = $rootJob;
    }

    /**
     * @param string   $ownerId
     * @param string   $name
     * @param \Closure $runCallback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function runUnique($ownerId, $name, \Closure $runCallback)
    {
        $lockHandler = new LockHandler(sprintf('%s_%s', 'oro_message_queue_unique_job', $name));
        if (!$lockHandler->lock()) {
            return null;
        }

        $rootJob = $this->jobProcessor->findOrCreateRootJob($ownerId, $name, true);
        if (!$rootJob) {
            $rootJob = $this->jobProcessor->findRootJobByUniqueName($name);
            if (!$rootJob) {
                $lockHandler->release();
                return null;
            }
        }

        $childJob = $this->jobProcessor->findOrCreateChildJob($name, $rootJob);

        if ($rootJob->isInterrupted()) {
            $this->jobProcessor->cancelAllActiveChildJobs($rootJob);

            return null;
        }

        if ($this->isReadyForStart($childJob)) {
            $this->jobProcessor->startChildJob($childJob);
        }

        $result = $this->callbackResult($runCallback, $childJob);

        if (! $childJob->getStoppedAt()) {
            $result
                ? $this->jobProcessor->successChildJob($childJob)
                : $this->jobProcessor->failChildJob($childJob);
        }

        $lockHandler->release();

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

        $jobRunner = new JobRunner($this->jobProcessor, $this->rootJob);

        return call_user_func($startCallback, $jobRunner, $childJob);
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

        if ($this->isReadyForStart($job)) {
            $this->jobProcessor->startChildJob($job);
        }

        $result = $this->callbackResult($runCallback, $job);

        if (! $job->getStoppedAt()) {
            $result
                ? $this->jobProcessor->successChildJob($job)
                : $this->jobProcessor->failChildJob($job);
        }

        return $result;
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
        $jobRunner = new JobRunner($this->jobProcessor, $job->getRootJob());
        try {
            $result = call_user_func($runCallback, $jobRunner, $job);
        } catch (\Exception $e) {
            $this->jobProcessor->failAndRedeliveryChildJob($job);

            throw $e;
        }

        return $result;
    }
}

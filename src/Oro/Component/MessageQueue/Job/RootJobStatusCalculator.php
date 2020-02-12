<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

/**
 * Calculate root job status and progress.
 * Sent message with topic `oro.message_queue.job.root_job_stopped` if job status was changed and job is stopped.
 */
class RootJobStatusCalculator implements RootJobStatusCalculatorInterface
{
    /** @var JobStorage */
    private $jobStorage;

    /** @var JobStatusChecker */
    private $jobStatusChecker;

    /** @var StatusCalculatorResolver */
    private $statusCalculatorResolver;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /**
     * @param JobStorage $jobStorage
     * @param JobStatusChecker $jobStatusChecker
     * @param StatusCalculatorResolver $statusCalculatorResolver
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        JobStorage $jobStorage,
        JobStatusChecker $jobStatusChecker,
        StatusCalculatorResolver $statusCalculatorResolver,
        MessageProducerInterface $messageProducer
    ) {
        $this->jobStorage = $jobStorage;
        $this->jobStatusChecker = $jobStatusChecker;
        $this->statusCalculatorResolver = $statusCalculatorResolver;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param Job $job
     * @return void
     */
    public function calculate(Job $job): void
    {
        $rootJob = $this->getRootJob($job);
        if ($this->jobStatusChecker->isJobStopped($rootJob)) {
            return;
        }

        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) {
            if (!$this->jobStatusChecker->isJobStopped($rootJob)) {
                $this->updateRootJob($rootJob);
            }
        });
    }

    /**
     * @param Job $rootJob
     * @return void
     */
    private function updateRootJob(Job $rootJob): void
    {
        $rootJob->setLastActiveAt(new \DateTime());

        $statusAndProgressCalculator = $this->statusCalculatorResolver->getCalculatorForRootJob($rootJob);
        $rootJobStatus = $statusAndProgressCalculator->calculateRootJobStatus();
        $rootJob->setStatus($rootJobStatus);

        if ($this->jobStatusChecker->isJobStopped($rootJob)) {
            $rootJob->setStoppedAt(new \DateTime());
        }

        $progress = $statusAndProgressCalculator->calculateRootJobProgress();
        if ($rootJob->getJobProgress() !== $progress) {
            $rootJob->setJobProgress($progress);
        }

        $statusAndProgressCalculator->clean();

        if ($this->jobStatusChecker->isJobStopped($rootJob)) {
            $message = new Message(['jobId' => $rootJob->getId()], MessagePriority::HIGH);
            $this->messageProducer->send(Topics::ROOT_JOB_STOPPED, $message);
        }
    }

    /**
     * @param Job $job
     * @return Job
     */
    private function getRootJob(Job $job): Job
    {
        return $job->isRoot() ? $job : $job->getRootJob();
    }
}

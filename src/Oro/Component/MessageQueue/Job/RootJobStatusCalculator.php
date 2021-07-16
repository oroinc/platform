<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Bundle\EntityBundle\ORM\Registry;
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
    private JobManagerInterface $jobManager;
    private JobStatusChecker $jobStatusChecker;
    private StatusCalculatorResolver $statusCalculatorResolver;
    private MessageProducerInterface $messageProducer;
    private Registry $registry;

    public function __construct(
        JobManagerInterface $jobManager,
        JobStatusChecker $jobStatusChecker,
        StatusCalculatorResolver $statusCalculatorResolver,
        MessageProducerInterface $messageProducer,
        Registry $registry
    ) {
        $this->jobManager = $jobManager;
        $this->jobStatusChecker = $jobStatusChecker;
        $this->statusCalculatorResolver = $statusCalculatorResolver;
        $this->messageProducer = $messageProducer;
        $this->registry = $registry;
    }

    public function calculate(Job $job): void
    {
        $rootJob = $this->getRootJob($job);
        $this->jobManager->saveJobWithLock($rootJob, function (Job $rootJob) {
            $rootJob = $this->refreshJob($rootJob);
            if (!$this->jobStatusChecker->isJobStopped($rootJob)) {
                $this->updateRootJob($rootJob);
            }
        });
    }

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

    private function getRootJob(Job $job): Job
    {
        return $job->isRoot() ? $job : $job->getRootJob();
    }

    private function refreshJob(Job $job): Job
    {
        $em = $this->registry->getManager();
        if ($em->contains($job)) {
            $em->refresh($job);
        }

        return $job;
    }
}

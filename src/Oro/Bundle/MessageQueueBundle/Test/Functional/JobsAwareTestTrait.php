<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\Testing\Assert\UniqueJobsProcessedConstraint;

/**
 * Provides useful assertion methods for the jobs management related functional tests.
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait JobsAwareTestTrait
{
    protected function getJobManager(): JobManager
    {
        return $this->getContainer()->get('oro_message_queue.job.manager');
    }

    protected function getJobRunner(): JobRunner
    {
        return $this->getContainer()->get('oro_message_queue.job.runner');
    }

    protected function getJobProcessor(): JobProcessor
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }

    protected function createUniqueJob(): ?Job
    {
        $ownerId = $this->getUniqid();
        $jobName = $this->getUniqid();
        $rootJob = $this->getJobProcessor()->findOrCreateRootJob($ownerId, $jobName, true);
        if (!$rootJob) {
            return null;
        }

        $childJob = $this->getJobProcessor()->findOrCreateChildJob($jobName, $rootJob);
        $this->getJobProcessor()->startChildJob($childJob);

        return $childJob;
    }

    protected function createDelayedJob(Job $rootJob = null): Job
    {
        if (!$rootJob) {
            $rootJob = $this->createUniqueJob()->getRootJob();
        }

        return $this->getJobProcessor()->findOrCreateChildJob($this->getUniqid(), $rootJob);
    }

    protected function getDependentJobsByJobId(int $jobId): array
    {
        $job = $this->getJobProcessor()->findJobById($jobId);
        $rootJob = $job->getRootJob() ?: $job;

        return $rootJob->getData()['dependentJobs'] ?? [];
    }

    protected function createRootJobMyMessage(MessageInterface $message)
    {
        $this->getJobProcessor()->findOrCreateRootJob(
            $message->getMessageId(),
            $this->getJobRunner()->getJobNameByMessage($message),
            true
        );
    }

    protected function getUniqid(): string
    {
        return uniqid(microtime(true), true);
    }

    protected function assertJobStatus(string $status, int $jobId): void
    {
        self::assertEquals($status, $this->getJobProcessor()->findJobById($jobId)->getStatus());
    }

    protected function assertUniqueJobsProcessed(): void
    {
        self::assertThat($this->getJobManager(), new UniqueJobsProcessedConstraint);
    }

    protected function assertUniqueJobsNotProcessed(): void
    {
        self::assertThat($this->getJobManager(), new UniqueJobsProcessedConstraint(false));
    }
}

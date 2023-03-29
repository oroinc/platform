<?php

namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;

/**
 * Sets the failed status for the child job found in a rejected message.
 */
class ChildJobFailingExtension extends AbstractExtension
{
    public const JOB_ID = 'jobId';
    public const IGNORED_JOB_STATUSES = [Job::STATUS_FAILED, Job::STATUS_CANCELLED, Job::STATUS_STALE];

    private JobProcessor $jobProcessor;

    private string $jobIdOption;

    private array $ignoredJobStatuses;

    public function __construct(
        JobProcessor $jobProcessor,
        string $jobIdOption = self::JOB_ID,
        array $ignoredJobStatuses = self::IGNORED_JOB_STATUSES
    ) {
        $this->jobProcessor = $jobProcessor;
        $this->jobIdOption = $jobIdOption;
        $this->ignoredJobStatuses = $ignoredJobStatuses;
    }

    public function onPostReceived(Context $context): void
    {
        parent::onPostReceived($context);

        $message = $context->getMessage();
        if (!$message) {
            $context
                ->getLogger()
                ->info('Message is missing in context, skipping extension');

            return;
        }
        if ($message->isRedelivered()) {
            return;
        }

        $jobId = $message->getBody()[$this->jobIdOption] ?? null;

        if (is_numeric($jobId) && $jobId > 0 && $context->getStatus() === MessageProcessorInterface::REJECT) {
            $job = $this->jobProcessor->findJobById((int)$jobId);
            if (!$job) {
                $context
                    ->getLogger()
                    ->info(
                        'Child job #{jobId} is not found for the rejected message #{messageId}',
                        [
                            'jobId' => $jobId,
                            'messageId' => $message->getMessageId(),
                        ]
                    );

                return;
            }

            if (!$job->isRoot() && !in_array($job->getStatus(), $this->ignoredJobStatuses, false)) {
                $this->jobProcessor->failChildJob($job);

                $context
                    ->getLogger()
                    ->info(
                        'Child job #{jobId} status is set to "{status}" for the rejected message #"{messageId}"',
                        [
                            'jobId' => $jobId,
                            'status' => Job::STATUS_CANCELLED,
                            'messageId' => $message->getMessageId(),
                        ]
                    );
            }
        }
    }
}

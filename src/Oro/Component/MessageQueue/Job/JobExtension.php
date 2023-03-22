<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * The Job extension. Cancel Unique Job in status if the message is requeued or rejected
 */
class JobExtension extends AbstractExtension
{
    private JobRunner $jobRunner;

    public function __construct(JobRunner $jobRunner)
    {
        $this->jobRunner = $jobRunner;
    }

    public function onPostReceived(Context $context)
    {
        if ($context->getStatus() === MessageProcessorInterface::REQUEUE) {
            return;
        }

        $this->cancelJobIfStatusNew($context->getMessage());
    }

    public function onInterrupted(Context $context)
    {
        if ($context->getStatus() !== MessageProcessorInterface::REJECT) {
            return;
        }

        $this->cancelJobIfStatusNew($context->getMessage());
    }

    private function cancelJobIfStatusNew(MessageInterface $message): void
    {
        if ($message->isRedelivered()) {
            return;
        }

        $jobName = $message->getProperty(JobAwareTopicInterface::UNIQUE_JOB_NAME);
        $this->jobRunner->cancelUniqueIfStatusNew($message->getMessageId(), $jobName);
    }
}

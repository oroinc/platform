<?php
declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Test\Async;

use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleChildJobTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message processor that runs a delayed job.
 */
class SampleChildJobProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;

    public function __construct(JobRunner $jobRunner)
    {
        $this->jobRunner = $jobRunner;
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $this->jobRunner->runDelayed($message->getBody()['jobId'], function (JobRunner $jobRunner, Job $job) {
            return true;
        });

        return MessageProcessorInterface::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [SampleChildJobTopic::getName()];
    }
}

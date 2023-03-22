<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerMiddlewareInterface;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;

/**
 * Creates unique job automatically before sending the message if the self::UNIQUE_JOB_NAME is provided in the message
 */
class JobMiddleware implements MessageProducerMiddlewareInterface
{
    private JobRunner $jobRunner;
    private TopicRegistry $topicRegistry;
    private JobProcessor $jobProcessor;

    public function __construct(JobRunner $jobRunner, TopicRegistry $topicRegistry, JobProcessor $jobProcessor)
    {
        $this->topicRegistry = $topicRegistry;
        $this->jobRunner = $jobRunner;
        $this->jobProcessor = $jobProcessor;
    }

    public function handle(Message $message): void
    {
        if ($this->hasMessageAttachedJob($message)) {
            return;
        }

        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        $topic = $this->topicRegistry->getJobAware($topicName);
        if ($topic) {
            $jobName = $topic->createJobName($message->getBody());
            $message->setProperty(JobAwareTopicInterface::UNIQUE_JOB_NAME, $jobName);
            $this->jobRunner->createUnique($message->getMessageId(), $jobName);
        }
    }

    private function hasMessageAttachedJob(Message $message): bool
    {
        $messageBody = $message->getBody();

        if (isset($messageBody['jobId'])) {
            $job = $this->jobProcessor->findJobById($messageBody['jobId']);

            if ($job) {
                return true;
            }
        }

        return false;
    }
}

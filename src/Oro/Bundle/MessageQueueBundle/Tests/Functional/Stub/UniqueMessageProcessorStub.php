<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Stub;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class UniqueMessageProcessorStub implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TEST_TOPIC = 'oro.message_queue.unique_test_topic';
    const TEST_JOB_NAME = 'test_job_unique|123456789';

    /** @var JobRunner */
    private $jobRunner;

    public function __construct(JobRunner $jobRunner)
    {
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = JSON::decode($message->getBody());

        if (false === is_array($messageBody)) {
            return self::REJECT;
        }

        $ownerId = $message->getMessageId();

        return $this->runUnique($ownerId) ? self::ACK : self::REJECT;
    }

    /**
     * @param string $ownerId
     *
     * @return bool
     */
    private function runUnique($ownerId)
    {
        $jobName = $this->buildJobNameForMessage();
        $closure = function () {
            return true;
        };

        return $this->jobRunner->runUnique($ownerId, $jobName, $closure);
    }

    /**
     * @return string
     */
    private function buildJobNameForMessage()
    {
        return self::TEST_JOB_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [self::TEST_TOPIC];
    }
}

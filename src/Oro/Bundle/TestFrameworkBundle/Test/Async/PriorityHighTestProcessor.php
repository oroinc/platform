<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Test\Async;

use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Test message processor subscribing to the high-priority test queue.
 * Used in functional tests for the `priority` consumption mode.
 */
class PriorityHighTestProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [
            PriorityHighTestTopic::getName() => ['destinationName' => 'test.priority.high'],
        ];
    }
}

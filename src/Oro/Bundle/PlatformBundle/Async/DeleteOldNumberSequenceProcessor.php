<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Async;

use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Bundle\PlatformBundle\Event\DeleteOldNumberSequenceEvent;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Processes messages to dispatch DeleteOldNumberSequenceEvent for deleting old number sequence entries.
 */
class DeleteOldNumberSequenceProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $this->eventDispatcher->dispatch(
            new DeleteOldNumberSequenceEvent(
                $body['sequenceType'],
                $body['discriminatorType']
            )
        );

        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [DeleteOldNumberSequenceTopic::getName()];
    }
}

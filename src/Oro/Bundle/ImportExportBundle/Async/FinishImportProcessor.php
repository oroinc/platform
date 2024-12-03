<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\ImportExportBundle\Async\Topic\FinishImportTopic;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\FinishImportEvent;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * When the import is complete, it sends an event notifying you of the completion.
 * Note that the event will be sent only when all child tasks are completed.
 */
class FinishImportProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function getSubscribedTopics(): array
    {
        return [FinishImportTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $event = new FinishImportEvent(
            $body['rootImportJobId'],
            $body['processorAlias'],
            $body['type'],
            $body['options']
        );

        $this->eventDispatcher->dispatch($event, Events::FINISH_IMPORT);

        return self::ACK;
    }
}

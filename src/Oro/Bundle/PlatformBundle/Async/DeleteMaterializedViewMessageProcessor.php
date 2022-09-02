<?php

namespace Oro\Bundle\PlatformBundle\Async;

use Oro\Bundle\PlatformBundle\Async\Topic\DeleteMaterializedViewTopic;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Drops the materialized view by the name specified in the message body.
 */
class DeleteMaterializedViewMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private MaterializedViewManager $materializedViewManager;

    public function __construct(MaterializedViewManager $materializedViewManager)
    {
        $this->materializedViewManager = $materializedViewManager;
    }

    public static function getSubscribedTopics(): array
    {
        return [DeleteMaterializedViewTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        $this->materializedViewManager->delete($messageBody['materializedViewName']);

        return self::ACK;
    }
}

<?php

namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Allows to route message to the correct queue based on topic.
 */
interface RecipientListRouterInterface
{
    public function getTopicSubscribers(string $topicName): array;

    /**
     * @param MessageInterface $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(MessageInterface $message);
}

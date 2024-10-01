<?php

namespace Oro\Bundle\SyncBundle\Manager;

use Gos\Bundle\WebSocketBundle\Topic\TopicManager as GosTopicManager;
use Ratchet\ConnectionInterface;

/**
 * Manages topics by regulating the reaction of application on topic events (onSubscribe, onClose, etc.)
 */
class TopicManager extends GosTopicManager
{
    #[\Override]
    public function onClose(ConnectionInterface $conn): void
    {
        $this->app->onClose($conn);

        foreach ($this->topicLookup as $topic) {
            $this->cleanTopic($topic, $conn);
            $this->app->onUnsubscribe($conn, $topic);
        }
    }
}

<?php

namespace Oro\Bundle\SyncBundle\Manager;

use Gos\Bundle\WebSocketBundle\Topic\TopicManager as GosTopicManager;
use Ratchet\ConnectionInterface;

/**
 * Manages topics by regulating the reaction of application on topic events (onSubscribe, onClose, etc.)
 */
class TopicManager extends GosTopicManager
{
    /**
     * {@inheritdoc}
     *
     * Overrides method to correctly handle the case when no subscribers left in topic after last connection is closed.
     * If the "onUnsubscribe()" is not called, then periodic timers would not be cleared properly and new subscribers
     * could not receive periodic messages.
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $this->app->onClose($conn);

        foreach ($this->topicLookup as $topic) {
            $this->cleanTopic($topic, $conn);
            $this->app->onUnsubscribe($conn, $topic);
        }
    }
}

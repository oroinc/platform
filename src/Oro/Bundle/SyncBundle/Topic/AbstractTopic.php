<?php

namespace Oro\Bundle\SyncBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * Provides common functionality for WebSocket topics.
 *
 * This base class implements the {@see TopicInterface} with default no-op implementations for subscription
 * and publication events.
 * Subclasses should override specific methods to handle topic-specific WebSocket messaging logic.
 */
abstract class AbstractTopic implements TopicInterface
{
    /** @var string */
    protected $topicName;

    public function __construct(string $topicName)
    {
        $this->topicName = $topicName;
    }

    #[\Override]
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
    }

    #[\Override]
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->topicName;
    }
}

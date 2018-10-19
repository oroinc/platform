<?php

namespace Oro\Bundle\SyncBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * Used to broadcast messages to clients to prevent connection loose
 * by default, the connection could be closed if no data were transmitted between client and server
 * @link http://nginx.org/en/docs/http/websocket.html
 */
class WebsocketPingTopic extends AbstractTopic implements TopicPeriodicTimerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use TopicPeriodicTimerTrait;

    /** @var int */
    private $timeout;

    /**
     * @param string $topicName
     * @param int $timeout
     */
    public function __construct(string $topicName, int $timeout = 50)
    {
        parent::__construct($topicName);

        $this->timeout = $timeout;
        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function registerPeriodicTimer(Topic $topic)
    {
        $this->periodicTimer->addPeriodicTimer(
            $this,
            $this->getName(),
            $this->timeout,
            function () use ($topic) {
                try {
                    $topic->broadcast('Connection keep alive');
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Failed to broadcast a message to the topic "oro/ping"',
                        ['exception' => $e]
                    );
                }
            }
        );
    }
}

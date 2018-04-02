<?php

namespace Oro\Bundle\SyncBundle\Authentication;

use JDare\ClankBundle\Server\App\Handler\TopicHandler;
use JDare\ClankBundle\Server\App\Handler\TopicHandlerInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\Topic;

/**
 * Clank topic handler decorator that adds authentication for the topics via Sync authentication tickets.
 */
class AuthenticationTopicHandler implements TopicHandlerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var TopicHandler */
    private $innerHandler;

    /** @var Topic[] [topic id => topic, ...] */
    private $subscribedTopics = [];

    /**
     * @param TopicHandler $innerHandler
     * @param LoggerInterface $logger
     */
    public function __construct(TopicHandler $innerHandler, LoggerInterface $logger)
    {
        $this->innerHandler = $innerHandler;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onSubscribe(Conn $conn, $topic)
    {
        /** @var Topic $topic */

        // save the topics to inner cache
        if (!array_key_exists($topic->getId(), $this->subscribedTopics)) {
            $this->subscribedTopics[$topic->getId()] = $topic;
        }

        if (!isset($conn->Authenticated) || !$conn->Authenticated) {
            $this->logger->warning(
                'Skip subscribing to the topic "{topic}", because the client is not authenticated',
                [
                    'remoteAddress' => $conn->remoteAddress,
                    'connectionId'  => $conn->resourceId,
                    'topic'         => $topic->getId()
                ]
            );
            // remove not authorized client from the topic
            $topic->remove($conn);
        }

        $this->innerHandler->onSubscribe($conn, $topic);
    }

    /**
     * {@inheritdoc}
     */
    public function onUnSubscribe(Conn $conn, $topic)
    {
        /** @var Topic $topic */
        $this->logger->debug(
            'Unsubscribe client from the topic "{topic}"',
            [
                'remoteAddress' => $conn->remoteAddress,
                'connectionId'  => $conn->resourceId,
                'topic'         => $topic->getId()
            ]
        );
        $this->innerHandler->onUnSubscribe($conn, $topic);
    }

    /**
     * {@inheritdoc}
     */
    public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible)
    {
        /** @var Topic $topic */
        if (isset($conn->Authenticated) && $conn->Authenticated) {
            $this->logger->debug(
                'Send a message to the topic "{topic}"',
                [
                    'remoteAddress' => $conn->remoteAddress,
                    'connectionId'  => $conn->resourceId,
                    'topic'         => $topic->getId()
                ]
            );

            $this->innerHandler->onPublish($conn, $topic, $event, $exclude, $eligible);
        } else {
            $this->logger->warning(
                'Skip the sending of a message to the topic "{topic}", because the client is not authenticated',
                [
                    'remoteAddress' => $conn->remoteAddress,
                    'connectionId'  => $conn->resourceId,
                    'topic'         => $topic->getId()
                ]
            );
        }
    }

    /**
     * Returns topics that was detected with subscription.
     *
     * @return Topic[] [topic id => topic, ...]
     */
    public function getSubscribedTopics()
    {
        return $this->subscribedTopics;
    }
}

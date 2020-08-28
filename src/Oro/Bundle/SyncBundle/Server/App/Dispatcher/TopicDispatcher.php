<?php

namespace Oro\Bundle\SyncBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher as DecoratedTopicDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * Decorate original TopicDispatcher
 *  - fix GHSA-wwgf-3xp7-cxj4 security issue (fix back ported from 1.10.4 version)
 */
class TopicDispatcher implements TopicDispatcherInterface
{
    /**
     * @var TopicDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param TopicDispatcherInterface $dispatcher
     */
    public function __construct(TopicDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request)
    {
        $this->dispatcher->onSubscribe($conn, $topic, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request)
    {
        $this->dispatcher->onUnSubscribe($conn, $topic, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function onPublish(
        ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ) {
        $this->dispatch(DecoratedTopicDispatcher::PUBLISH, $conn, $topic, $request, $event, $exclude, $eligible);
    }

    /**
     * {@inheritDoc}
     */
    public function onPush(WampRequest $request, $data, $provider)
    {
        $this->dispatcher->onPush($request, $data, $provider);
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(
        $calledMethod,
        ?ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $payload = null,
        $exclude = null,
        $eligible = null,
        $provider = null
    ) {
        return $this->dispatcher->dispatch(
            $calledMethod,
            $conn,
            $topic,
            $request,
            $payload,
            $exclude,
            $eligible,
            $provider
        );
    }
}

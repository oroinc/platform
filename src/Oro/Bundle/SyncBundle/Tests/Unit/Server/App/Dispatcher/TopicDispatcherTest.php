<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Oro\Bundle\SyncBundle\Server\App\Dispatcher\TopicDispatcher;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class TopicDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var TopicDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedDispatcher;

    /** @var TopicDispatcher */
    private $dispatcher;

    public function setUp(): void
    {
        $this->decoratedDispatcher = $this->createMock(TopicDispatcherInterface::class);
        $this->dispatcher = new TopicDispatcher($this->decoratedDispatcher);
    }

    public function testOnSubscribe(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $topic = $this->createMock(Topic::class);
        $request = $this->createMock(WampRequest::class);

        $this->decoratedDispatcher
            ->expects($this->once())
            ->method('onSubscribe')
            ->with($connection, $topic, $request);

        $this->dispatcher->onSubscribe($connection, $topic, $request);
    }

    public function testOnUnSubscribe(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $topic = $this->createMock(Topic::class);
        $request = $this->createMock(WampRequest::class);

        $this->decoratedDispatcher
            ->expects($this->once())
            ->method('onUnSubscribe')
            ->with($connection, $topic, $request);

        $this->dispatcher->onUnSubscribe($connection, $topic, $request);
    }

    public function testOnPublish(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $topic = $this->createMock(Topic::class);
        $request = $this->createMock(WampRequest::class);
        $event = 'sample_event';
        $exclude = ['sample_exclude'];
        $eligible = ['sample_eligible'];

        $this->decoratedDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('onPublish', $connection, $topic, $request, $event, $exclude, $eligible);

        $this->dispatcher->onPublish($connection, $topic, $request, $event, $exclude, $eligible);
    }

    public function testOnPush(): void
    {
        $request = $this->createMock(WampRequest::class);
        $data = ['sample_data'];
        $provider = 'test_provider';

        $this->decoratedDispatcher
            ->expects($this->once())
            ->method('onPush')
            ->with($request, $data, $provider);

        $this->dispatcher->onPush($request, $data, $provider);
    }

    public function dispatch(): void
    {
        $calledMethod = DecoratedTopicDispatcher::PUBLISH;
        $connection = $this->createMock(ConnectionInterface::class);
        $topic = $this->createMock(Topic::class);
        $request = $this->createMock(WampRequest::class);
        $payload = ['sample_data'];
        $exclude = ['sample_exclude'];
        $eligible = ['sample_eligible'];
        $provider = 'test_provider';

        $result = true;
        $this->decoratedDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($calledMethod, $connection, $topic, $request, $payload, $exclude, $eligible, $provider)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->dispatcher->dispatch(
                $calledMethod,
                $connection,
                $topic,
                $request,
                $payload,
                $exclude,
                $eligible,
                $provider
            )
        );
    }
}

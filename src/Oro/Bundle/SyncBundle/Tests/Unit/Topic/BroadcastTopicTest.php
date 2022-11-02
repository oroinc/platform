<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Topic;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\BroadcastTopic;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class BroadcastTopicTest extends \PHPUnit\Framework\TestCase
{
    private BroadcastTopic $broadcast;

    protected function setUp(): void
    {
        $this->broadcast = new BroadcastTopic('broadcast_topic');
    }

    public function testGetName(): void
    {
        self::assertSame('broadcast_topic', $this->broadcast->getName());
    }

    public function testOnPublish(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::never())
            ->method(self::anything());

        $event = new \stdClass();

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('broadcast')
            ->with($event, ['data1'], ['data2']);

        $wampRequest = new WampRequest(
            'route',
            $this->createMock(Route::class),
            $this->createMock(ParameterBag::class),
            'matched'
        );

        $this->broadcast->onPublish($connection, $topic, $wampRequest, $event, ['data1'], ['data2']);
    }
}

<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\BroadcastTopic;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class BroadcastTopicTest extends \PHPUnit_Framework_TestCase
{
    /** @var BroadcastTopic */
    private $broadcast;

    protected function setUp()
    {
        $this->broadcast = new BroadcastTopic('broadcast_topic');
    }

    public function testGetName(): void
    {
        self::assertSame('broadcast_topic', $this->broadcast->getName());
    }

    public function testOnPublish(): void
    {
        /** @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::never())
            ->method(self::anything());

        $event = new \stdClass();

        /** @var Topic|\PHPUnit_Framework_MockObject_MockObject $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('broadcast')
            ->with($event, ['data1'], ['data2']);

        /** @var WampRequest|\PHPUnit_Framework_MockObject_MockObject $wampRequest */
        $wampRequest = $this->createMock(WampRequest::class);
        $wampRequest->expects(self::never())
            ->method(self::anything());

        $this->broadcast->onPublish($connection, $topic, $wampRequest, $event, ['data1'], ['data2']);
    }
}

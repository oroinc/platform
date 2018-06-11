<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Topic;

use Oro\Bundle\SyncBundle\Topic\BroadcastTopic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class BroadcastTopicTest extends \PHPUnit_Framework_TestCase
{
    /** @var BroadcastTopic */
    protected $broadcast;

    protected function setUp()
    {
        $this->broadcast = new BroadcastTopic('broadcast_topic');
    }

    public function testGetName()
    {
        self::assertSame('broadcast_topic', $this->broadcast->getName());
    }

    public function testOnPublish()
    {
        /** @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->never())
            ->method($this->anything());

        $event = new \stdClass();

        /** @var Topic|\PHPUnit_Framework_MockObject_MockObject $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('broadcast')
            ->with($event, ['data1'], ['data2']);

        /** @var WampRequest|\PHPUnit_Framework_MockObject_MockObject $wampRequest */
        $wampRequest = $this->createMock(WampRequest::class);
        $wampRequest->expects($this->never())
            ->method($this->anything());

        $this->broadcast->onPublish($connection, $topic, $wampRequest, $event, ['data1'], ['data2']);
    }
}

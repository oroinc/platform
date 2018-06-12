<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\SecuredTopic;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class SecuredTopicTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ClientManipulatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $clientManipulator;

    /** @var SecuredTopic */
    protected $securedTopic;

    /** @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var Topic|\PHPUnit_Framework_MockObject_MockObject */
    protected $topic;

    /** @var ParameterBag|\PHPUnit_Framework_MockObject_MockObject */
    protected $parameterBag;

    /** @var WampRequest|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    protected function setUp()
    {
        $this->clientManipulator = $this->createMock(ClientManipulatorInterface::class);

        $this->securedTopic = new SecuredTopic('test_topic', $this->clientManipulator);

        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->connection->WAMP = (object)['sessionId' => '12345'];

        $this->topic = $this->createMock(Topic::class);
        $this->topic->expects($this->any())
            ->method('getId')
            ->willReturn('test_subscription');
        $this->topic->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->connection]));

        $this->parameterBag = $this->createMock(ParameterBag::class);

        $this->request = $this->createMock(WampRequest::class);
        $this->request->expects($this->any())
            ->method('getAttributes')
            ->willReturn($this->parameterBag);
    }

    public function testOnSubscribeWithoutUserId()
    {
        $this->parameterBag->expects($this->once())
            ->method('getInt')
            ->with('user_id')
            ->willReturn(0);

        $this->clientManipulator->expects($this->never())
            ->method($this->anything());

        $this->topic->expects($this->once())
            ->method('remove')
            ->with($this->connection);

        $this->connection->expects($this->once())
            ->method('send')
            ->with('You are not allowed to subscribe on topic "test_subscription".');

        $this->securedTopic->onSubscribe($this->connection, $this->topic, $this->request);
    }

    public function testOnSubscribeIncorrectUserId()
    {
        $this->parameterBag->expects($this->once())
            ->method('getInt')
            ->with('user_id')
            ->willReturn(2002);

        $this->clientManipulator->expects($this->once())
            ->method('getClient')
            ->with($this->connection)
            ->willReturn($this->getEntity(User::class, ['id' => 1001]));

        $this->topic->expects($this->once())
            ->method('remove')
            ->with($this->connection);

        $this->connection->expects($this->once())
            ->method('send')
            ->with('You are not allowed to subscribe on topic "test_subscription".');

        $this->securedTopic->onSubscribe($this->connection, $this->topic, $this->request);
    }

    public function testOnSubscribe()
    {
        $this->parameterBag->expects($this->once())
            ->method('getInt')
            ->with('user_id')
            ->willReturn(1001);

        $this->clientManipulator->expects($this->once())
            ->method('getClient')
            ->with($this->connection)
            ->willReturn($this->getEntity(User::class, ['id' => 1001]));

        $this->topic->expects($this->never())
            ->method('remove');

        $this->connection->expects($this->never())
            ->method($this->anything());

        $this->securedTopic->onSubscribe($this->connection, $this->topic, $this->request);
    }
}

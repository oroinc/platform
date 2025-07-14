<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Topic;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\SyncBundle\Topic\SecuredTopic;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class SecuredTopicTest extends TestCase
{
    use EntityTrait;

    private ClientManipulatorInterface&MockObject $clientManipulator;
    private SecuredTopic $securedTopic;
    private ConnectionInterface&MockObject $connection;
    private Topic&MockObject $topic;
    private ParameterBag&MockObject $parameterBag;
    private WampRequest $request;

    #[\Override]
    protected function setUp(): void
    {
        $this->clientManipulator = $this->createMock(ClientManipulatorInterface::class);

        $this->securedTopic = new SecuredTopic('test_topic', $this->clientManipulator);

        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->connection->WAMP = (object)['sessionId' => '12345'];

        $this->topic = $this->createMock(Topic::class);
        $this->topic->expects(self::any())
            ->method('getId')
            ->willReturn('test_subscription');
        $this->topic->expects(self::any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->connection]));

        $this->parameterBag = $this->createMock(ParameterBag::class);
        $this->request = new WampRequest('route', $this->createMock(Route::class), $this->parameterBag, 'matched');
    }

    public function testOnSubscribeWithoutUserId(): void
    {
        $this->parameterBag->expects(self::once())
            ->method('getInt')
            ->with('user_id')
            ->willReturn(0);

        $this->clientManipulator->expects(self::never())
            ->method(self::anything());

        $this->topic->expects(self::once())
            ->method('remove')
            ->with($this->connection);

        $this->connection->expects(self::once())
            ->method('send')
            ->with('You are not allowed to subscribe on topic "test_subscription".');

        $this->securedTopic->onSubscribe($this->connection, $this->topic, $this->request);
    }

    public function testOnSubscribeIncorrectUserId(): void
    {
        $this->parameterBag->expects(self::once())
            ->method('getInt')
            ->with('user_id')
            ->willReturn(2002);

        $this->clientManipulator->expects(self::once())
            ->method('getUser')
            ->with($this->connection)
            ->willReturn($this->getEntity(User::class, ['id' => 1001]));

        $this->topic->expects(self::once())
            ->method('remove')
            ->with($this->connection);

        $this->connection->expects(self::once())
            ->method('send')
            ->with('You are not allowed to subscribe on topic "test_subscription".');

        $this->securedTopic->onSubscribe($this->connection, $this->topic, $this->request);
    }

    public function testOnSubscribe(): void
    {
        $this->parameterBag->expects(self::once())
            ->method('getInt')
            ->with('user_id')
            ->willReturn(1001);

        $this->clientManipulator->expects(self::once())
            ->method('getUser')
            ->with($this->connection)
            ->willReturn($this->getEntity(User::class, ['id' => 1001]));

        $this->topic->expects(self::never())
            ->method('remove');

        $this->connection->expects(self::never())
            ->method(self::anything());

        $this->securedTopic->onSubscribe($this->connection, $this->topic, $this->request);
    }
}

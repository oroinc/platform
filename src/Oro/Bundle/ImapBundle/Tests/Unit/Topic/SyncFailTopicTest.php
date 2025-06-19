<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Topic;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\ImapBundle\Topic\SyncFailTopic;
use Oro\Bundle\SyncBundle\Client\ClientManipulator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class SyncFailTopicTest extends TestCase
{
    use EntityTrait;

    private ClientManipulator&MockObject $clientManipulator;
    private ConnectionInterface&MockObject $conn;
    private Topic&MockObject $topic;
    private ParameterBag&MockObject $parameterBag;
    private WampRequest $request;
    private SyncFailTopic $syncFailTopic;

    #[\Override]
    protected function setUp(): void
    {
        $this->conn = $this->createMock(ConnectionInterface::class);
        $this->topic = $this->createMock(Topic::class);
        $this->parameterBag = $this->createMock(ParameterBag::class);
        $this->request = new WampRequest(
            'sample_route',
            $this->createMock(Route::class),
            $this->parameterBag,
            'sample_match'
        );

        $this->clientManipulator = $this->createMock(ClientManipulator::class);
        $this->syncFailTopic = new SyncFailTopic('oro_imap.sync_fail', $this->clientManipulator);
    }

    public function testOnSubscribeGeneralTopic(): void
    {
        $this->parameterBag->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn('*');

        $this->topic->expects($this->never())
            ->method('remove');

        $this->syncFailTopic->onSubscribe($this->conn, $this->topic, $this->request);
    }

    public function testOnSubscribeWithoutUserId(): void
    {
        $this->parameterBag->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn('0');

        $this->conn->expects($this->once())
            ->method('send');

        $this->topic->expects($this->once())
            ->method('remove');

        $this->syncFailTopic->onSubscribe($this->conn, $this->topic, $this->request);
    }

    public function testOnSubscribeWithWrongId(): void
    {
        $user = $this->getEntity(User::class, ['id' => 111]);

        $this->clientManipulator->expects($this->once())
            ->method('getUser')
            ->with($this->conn)
            ->willReturn($user);

        $this->parameterBag->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn('333');

        $this->conn->expects($this->once())
            ->method('send');

        $this->topic->expects($this->once())
            ->method('remove');

        $this->syncFailTopic->onSubscribe($this->conn, $this->topic, $this->request);
    }

    public function testOnSubscribe(): void
    {
        $user = $this->getEntity(User::class, ['id' => 111]);

        $this->clientManipulator->expects($this->once())
            ->method('getUser')
            ->with($this->conn)
            ->willReturn($user);

        $this->parameterBag->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn('111');

        $this->topic->expects($this->never())
            ->method('remove');

        $this->syncFailTopic->onSubscribe($this->conn, $this->topic, $this->request);
    }
}

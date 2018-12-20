<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulator;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Oro\Bundle\ImapBundle\Topic\SyncFailTopic;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class SyncFailTopicTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ClientManipulator|\PHPUnit\Framework\MockObject\MockObject */
    protected $clientManipulator;

    /** @var ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $conn;

    /** @var Topic|\PHPUnit\Framework\MockObject\MockObject */
    protected $topic;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $parameterBag;

    /** @var WampRequest|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var SyncFailTopic */
    protected $syncFailTopic;

    public function setUp()
    {
        $this->conn = $this->createMock(ConnectionInterface::class);
        $this->topic = $this->createMock(Topic::class);
        $this->parameterBag = $this->createMock(ParameterBag::class);
        $this->request = $this->createMock(WampRequest::class);
        $this->request->expects($this->any())
            ->method('getAttributes')
            ->willReturn($this->parameterBag);

        $this->clientManipulator = $this->createMock(ClientManipulator::class);
        $this->syncFailTopic = new SyncFailTopic('oro_imap.sync_fail', $this->clientManipulator);
    }

    public function testOnSubscribeGeneralTopic()
    {
        $this->parameterBag->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn('*');

        $this->topic->expects($this->never())
            ->method('remove');

        $this->syncFailTopic->onSubscribe($this->conn, $this->topic, $this->request);
    }

    public function testOnSubscribeWithoutUserId()
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

    public function testOnSubscribeWithWrongId()
    {
        $user = $this->getEntity(User::class, ['id' => 111]);

        $this->clientManipulator->expects($this->once())
            ->method('getClient')
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

    public function testOnSubscribe()
    {
        $user = $this->getEntity(User::class, ['id' => 111]);

        $this->clientManipulator->expects($this->once())
            ->method('getClient')
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

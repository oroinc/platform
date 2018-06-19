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

class SyncFailTopicTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ClientManipulator|\PHPUnit_Framework_MockObject_MockObject */
    protected $clientManipulator;

    /** @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $conn;

    /** @var Topic|\PHPUnit_Framework_MockObject_MockObject */
    protected $topic;

    /** @var ParameterBag|\PHPUnit_Framework_MockObject_MockObject */
    protected $parameterBag;

    /** @var WampRequest|\PHPUnit_Framework_MockObject_MockObject */
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

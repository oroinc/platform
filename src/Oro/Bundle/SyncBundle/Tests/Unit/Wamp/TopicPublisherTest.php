<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Wamp;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Oro\Bundle\SyncBundle\Exception\WebSocket\Rfc6455Exception;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\SyncBundle\Wamp\WebSocket;
use Oro\Bundle\SyncBundle\Wamp\WebSocketClientAttributes;
use Oro\Bundle\SyncBundle\Wamp\WebSocketClientFactoryInterface;

class TopicPublisherTest extends \PHPUnit_Framework_TestCase
{
    private const TOPIC = 'Topic';
    private const MESSAGE = 'Message';

    /** @var WebSocket|\PHPUnit_Framework_MockObject_MockObject */
    private $socket;

    /** @var WebSocketClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $clientFactory;

    /** @var TicketProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $ticketProvider;

    /** @var TopicPublisher */
    private $wamp;

    protected function setUp()
    {
        $this->socket = $this->createMock(WebSocket::class);
        $this->clientFactory = $this->createMock(WebSocketClientFactoryInterface::class);

        $this->ticketProvider = $this->createMock(TicketProvider::class);
        $this->ticketProvider->expects($this->any())
            ->method('generateTicket')
            ->with(true)
            ->willReturn('test_ticket');

        $this->wamp = new TopicPublisher('example.com', 1, '/test');
        $this->wamp->setClientFactory($this->clientFactory);
        $this->wamp->setTicketProvider($this->ticketProvider);
    }

    public function testSend()
    {
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->with(
                new WebSocketClientAttributes(
                    'example.com',
                    1,
                    '/test?ticket=test_ticket',
                    'tcp',
                    []
                )
            )
            ->willReturn($this->socket);

        $this->assertTrue($this->wamp->send(self::TOPIC, self::MESSAGE));
    }

    public function testCheckTrue()
    {
        $this->assertTrue($this->wamp->check());
    }

    public function testCheckFalse()
    {
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->willThrowException(new Rfc6455Exception());

        $this->assertFalse($this->wamp->check());
    }
}

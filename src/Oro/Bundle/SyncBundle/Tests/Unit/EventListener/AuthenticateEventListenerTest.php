<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Psr\Log\LoggerInterface;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampConnection;
use Ratchet\WebSocket\Version\RFC6455\Connection;

use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;

use JDare\ClankBundle\Event\ClientEvent;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Oro\Bundle\SyncBundle\EventListener\AuthenticateEventListener;

class AuthenticateEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AuthenticateEventListener */
    private $listener;

    /** @var QueryString */
    private $query;

    /** @var ClientEvent */
    private $event;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $ticketProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        $this->ticketProvider = $this->createMock(TicketProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new AuthenticateEventListener($this->ticketProvider, $this->logger);

        $this->query = new QueryString();

        $url = new Url('http', 'domain.com');
        $url->setQuery($this->query);

        $request = new EntityEnclosingRequest('http', 'http://domain.com');
        $request->setUrl($url);

        $webSocket = new \stdClass();
        $webSocket->request = $request;

        $connection = new WampConnection(new Connection($this->createMock(ConnectionInterface::class)));
        $connection->WebSocket = $webSocket;
        $connection->remoteAddress = 'localhost';
        $connection->resourceId = 45654;

        $this->event = new ClientEvent($connection, 1);
    }

    public function testOnClientConnectWithoutTicketInUrl()
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Sync ticket was not found in the request',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654]
            );

        $this->listener->onClientConnect($this->event);

        $this->assertFalse($this->event->getConnection()->Authenticated);
    }

    public function testOnClientConnectWithValidTicketInUrl()
    {
        $this->query->add('ticket', 'valid_ticket');

        $this->ticketProvider->expects($this->once())
            ->method('isTicketValid')
            ->with('valid_ticket')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Sync ticket was found in the request',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654]
            );

        $this->listener->onClientConnect($this->event);

        $this->assertTrue($this->event->getConnection()->Authenticated);
    }

    public function testOnClientConnectWithValidNotTicketInUrl()
    {
        $this->query->add('ticket', 'not_valid_ticket');

        $this->ticketProvider->expects($this->once())
            ->method('isTicketValid')
            ->with('not_valid_ticket')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Sync ticket was found in the request',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654]
            );

        $this->listener->onClientConnect($this->event);

        $this->assertFalse($this->event->getConnection()->Authenticated);
    }
}

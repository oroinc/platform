<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\EventListener\AuthenticateEventListener;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampConnection;
use Ratchet\WebSocket\Version\RFC6455\Connection;

class AuthenticateEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    /**
     * @var TicketProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ticketProvider;

    /**
     * @var AuthenticateEventListener
     */
    private $authenticateEventListener;

    /**
     * @var QueryString
     */
    private $query;

    /**
     * @var ClientEvent
     */
    private $clientEvent;

    protected function setUp()
    {
        $this->ticketProvider = $this->createMock(TicketProvider::class);

        $this->authenticateEventListener = new AuthenticateEventListener($this->ticketProvider);

        $this->setUpLoggerMock($this->authenticateEventListener);

        $this->query = new QueryString();

        $url = new Url('http', 'domain.com');
        $url->setQuery($this->query);

        $request = new EntityEnclosingRequest('http', 'http://domain.com');
        $request->setUrl($url);

        $conn = $this->createMock(ConnectionInterface::class);
        $conn->WebSocket = new \StdClass();
        $conn->WebSocket->request = $request;
        $conn->WebSocket->established = false;
        $conn->WebSocket->closing = false;
        $conn->remoteAddress = 'localhost';
        $conn->resourceId = 45654;

        $connection = new WampConnection(new Connection($conn));

        $this->clientEvent = new ClientEvent($connection, 1);
    }

    public function testOnClientConnectWithoutTicketInUrl()
    {
        $this->assertLoggerWarningMethodCalled();

        $this->authenticateEventListener->onClientConnect($this->clientEvent);

        self::assertFalse($this->clientEvent->getConnection()->Authenticated);
    }

    public function testOnClientConnectWithValidTicketInUrl()
    {
        $this->query->add('ticket', 'valid_ticket');

        $this->ticketProvider
            ->expects(self::once())
            ->method('isTicketValid')
            ->with('valid_ticket')
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        $this->authenticateEventListener->onClientConnect($this->clientEvent);

        self::assertTrue($this->clientEvent->getConnection()->Authenticated);
    }

    public function testOnClientConnectWithInvalidTicketInUrl()
    {
        $this->query->add('ticket', 'not_valid_ticket');

        $this->ticketProvider
            ->expects(self::once())
            ->method('isTicketValid')
            ->with('not_valid_ticket')
            ->willReturn(false);

        $this->assertLoggerDebugMethodCalled();

        $this->authenticateEventListener->onClientConnect($this->clientEvent);

        self::assertFalse($this->clientEvent->getConnection()->Authenticated);
    }
}

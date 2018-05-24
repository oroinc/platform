<?php

namespace Oro\Bundle\SyncBundle\Tests\Functional\Controller;

use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Url;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TicketControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testSyncTicketAction()
    {
        $url = $this->getUrl('oro_sync_ticket');
        $this->client->request('POST', $url);

        $response = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($response['ticket']);

        $connection = $this->prepareConnection($response);

        $event = new ClientEvent($connection, ClientEvent::CONNECTED);
        $this->getContainer()
            ->get('event_dispatcher')
            ->dispatch('gos_web_socket.client_connected', $event);

        $user = $this->getContainer()
            ->get('gos_web_socket.websocket.client_manipulator')
            ->getClient($connection);

        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertSame('admin', $user->getUsername());
    }

    /**
     * @param array $response
     * @return ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareConnection(array $response)
    {
        $url = new Url('http', 'test.local');
        $url->setQuery($response);
        $request = new Request('GET', $url);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WebSocket = new \stdClass();
        $connection->WebSocket->request = $request;
        $connection->WAMP = new \stdClass();
        $connection->WAMP->sessionId = 'test-session-id';
        $connection->resourceId = 'test-resource-id';

        return $connection;
    }
}

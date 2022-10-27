<?php

namespace Oro\Bundle\SyncBundle\Tests\Functional\Controller;

use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TicketControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testSyncTicketAction(): void
    {
        $url = $this->getUrl('oro_sync_ticket');
        $this->ajaxRequest('POST', $url);

        $response = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertNotEmpty($response['ticket']);

        $connection = $this->prepareConnection($response['ticket']);

        $event = new ClientConnectedEvent($connection);
        self::getContainer()
            ->get('event_dispatcher')
            ->dispatch($event, 'gos_web_socket.client_connected');

        $user = self::getContainer()
            ->get('gos_web_socket.client.manipulator')
            ->getUser($connection);

        self::assertInstanceOf(UserInterface::class, $user);
        self::assertSame('admin', $user->getUsername());
    }

    /**
     * @param string $ticket
     *
     * @return ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareConnection(string $ticket): ConnectionInterface
    {
        $uri = (new Uri())
            ->withQuery('ticket=' . $ticket);

        $request = new Request('GET', $uri);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->httpRequest = $request;
        $connection->WAMP = new \stdClass();
        $connection->WAMP->sessionId = 'test-session-id';
        $connection->resourceId = 'test-resource-id';

        return $connection;
    }
}

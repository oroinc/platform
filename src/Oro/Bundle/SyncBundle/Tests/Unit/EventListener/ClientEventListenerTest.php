<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Oro\Bundle\SyncBundle\Authentication\Ticket\InMemoryAnonymousTicket;
use Oro\Bundle\SyncBundle\EventListener\ClientEventListener;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ClientEventListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const CONNECTION_RESOURCE_ID = 45654;

    private WebsocketAuthenticationProviderInterface&MockObject $websocketAuthenticationProvider;
    private ClientStorageInterface&MockObject $clientStorage;
    private ConnectionInterface&MockObject $connection;
    private ClientEventListener $clientEventListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->websocketAuthenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);

        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->connection->WAMP = (object)['sessionId' => '12345', 'prefixes' => []];
        $this->connection->remoteAddress = 'localhost';
        $this->connection->resourceId = self::CONNECTION_RESOURCE_ID;

        $this->clientEventListener = new ClientEventListener(
            $this->websocketAuthenticationProvider,
            $this->clientStorage
        );

        $this->setUpLoggerMock($this->clientEventListener);
    }

    public function testOnClientConnectAuthenticationFailed(): void
    {
        $this->websocketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willThrowException(new BadCredentialsException());

        $this->expectException(BadCredentialsException::class);

        $event = new ClientConnectedEvent($this->connection);
        $this->clientEventListener->onClientConnect($event);

        self::assertNull($this->connection->WAMP->clientStorageId);
    }

    public function testOnClientConnectWithStorageException(): void
    {
        $user = (new User())->setUserIdentifier('test');
        $token = new TicketToken($user, 'sampleKey', ['sampleRole']);
        $token->setAttribute('ticketId', 'sampleTicketDigest');
        $this->websocketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willReturn($token);

        $this->clientStorage->expects(self::once())
            ->method('addClient')
            ->willThrowException(new StorageException());

        $this->assertLoggerErrorMethodCalled();

        $this->expectException(StorageException::class);

        $event = new ClientConnectedEvent($this->connection);
        $this->clientEventListener->onClientConnect($event);

        self::assertNull($this->connection->WAMP->username);
    }

    public function testOnClientConnectAnonymous(): void
    {
        $token = new AnonymousTicketToken(
            'sampleTicketDigest',
            new InMemoryAnonymousTicket('anonymous-test')
        );
        $token->setAttribute('ticketId', 'sampleTicketDigest');

        $this->websocketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willReturn($token);

        $this->assertLoggerInfoMethodCalled();

        $event = new ClientConnectedEvent($this->connection);
        $this->clientEventListener->onClientConnect($event);

        self::assertNull($this->connection->WAMP->username);
    }

    public function testOnClientConnect(): void
    {
        $user = (new User())->setUserIdentifier('test');
        $token = new TicketToken($user, 'sampleKey', ['sampleRole']);
        $token->setAttribute('ticketId', 'sampleTicketDigest');

        $this->websocketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willReturn($token);

        $this->assertLoggerInfoMethodCalled();

        $event = new ClientConnectedEvent($this->connection);
        $this->clientEventListener->onClientConnect($event);

        self::assertEquals($user, $this->connection->WAMP->username);
    }

    public function testOnClientErrorGeneralException(): void
    {
        $clientErrorEvent = new ClientErrorEvent($this->connection);
        $clientErrorEvent->setException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $this->clientEventListener->onClientError($clientErrorEvent);
    }

    public function testOnClientErrorBadCredentialsException(): void
    {
        $clientErrorEvent = new ClientErrorEvent($this->connection);
        $clientErrorEvent->setException(new BadCredentialsException());

        $this->connection->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (string $data) {
                self::assertEquals(403, substr($data, -3));
            });
        $this->connection->expects(self::once())
            ->method('close');

        $this->assertLoggerInfoMethodCalled();

        $this->clientEventListener->onClientError($clientErrorEvent);

        self::assertTrue($clientErrorEvent->isPropagationStopped());
    }
}

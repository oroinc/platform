<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener as GosClientEventListener;
use Oro\Bundle\SyncBundle\EventListener\ClientEventListener;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ClientEventListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const CONNECTION_RESOURCE_ID = 45654;

    /** @var WebsocketAuthenticationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websocketAuthenticationProvider;

    /** @var ClientStorage */
    private $clientStorage;

    /** @var ClientEventListener */
    private $clientEventListener;

    /** @var GosClientEventListener|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedClientEventListener;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedListenerLogger;

    /** @var ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    protected function setUp(): void
    {
        $this->websocketAuthenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);

        $this->decoratedListenerLogger = $this->createMock(LoggerInterface::class);
        $this->decoratedClientEventListener = new GosClientEventListener(
            $this->clientStorage,
            $this->websocketAuthenticationProvider
        );
        $this->decoratedClientEventListener->setLogger($this->decoratedListenerLogger);

        $this->clientEventListener = new ClientEventListener(
            $this->decoratedClientEventListener,
            $this->websocketAuthenticationProvider,
            $this->clientStorage
        );

        $this->setUpLoggerMock($this->clientEventListener);

        $this->connection = $this->createConnection();
    }

    public function testOnClientConnectAuthenticationFailed(): void
    {
        $this->websocketAuthenticationProvider
            ->expects(self::once())
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
        $token = new TicketToken('sampleUser', 'sampleTicketDigest', 'sampleKey', ['sampleRole']);
        $this->websocketAuthenticationProvider
            ->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willReturn($token);

        $this->clientStorage
            ->expects(self::once())
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
            AuthenticationProviderInterface::USERNAME_NONE_PROVIDED
        );

        $this->websocketAuthenticationProvider
            ->expects(self::once())
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
        $user = 'sampleUser';
        $token = new TicketToken($user, 'sampleTicketDigest', 'sampleKey', ['sampleRole']);
        $this->websocketAuthenticationProvider
            ->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willReturn($token);

        $this->assertLoggerInfoMethodCalled();

        $event = new ClientConnectedEvent($this->connection);
        $this->clientEventListener->onClientConnect($event);

        self::assertEquals($user, $this->connection->WAMP->username);
    }

    public function testOnClientDisconnect(): void
    {
        $disconnectEvent = new ClientDisconnectedEvent($this->connection);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->willReturn(true);
        $this->decoratedListenerLogger
            ->expects(self::once())
            ->method('info');

        $this->clientEventListener->onClientDisconnect($disconnectEvent);
    }

    public function testOnClientRejected(): void
    {
        $clientRejectedEvent = new ClientRejectedEvent('sampleOrigin');

        $this->decoratedListenerLogger
            ->expects(self::once())
            ->method('warning')
            ->with('Client rejected, bad origin');

        $this->clientEventListener->onClientRejected($clientRejectedEvent);
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

        $this->decoratedListenerLogger
            ->expects(self::never())
            ->method('error');

        $closingResponse = new Response(403);
        $this->connection
            ->expects(self::once())
            ->method('send')
            ->with($closingResponse);
        $this->connection
            ->expects(self::once())
            ->method('close');

        $this->assertLoggerInfoMethodCalled();

        $this->clientEventListener->onClientError($clientErrorEvent);

        self::assertTrue($clientErrorEvent->isPropagationStopped());
    }

    private function createConnection(): ConnectionInterface
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = (object)['sessionId' => '12345', 'prefixes' => []];
        $connection->remoteAddress = 'localhost';
        $connection->resourceId = self::CONNECTION_RESOURCE_ID;

        return $connection;
    }
}

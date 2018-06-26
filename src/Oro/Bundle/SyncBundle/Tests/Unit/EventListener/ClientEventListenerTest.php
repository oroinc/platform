<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEventListener as GosClientEventListener;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Guzzle\Http\Message\Response;
use Oro\Bundle\SyncBundle\EventListener\ClientEventListener;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ClientEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const CONNECTION_RESOURCE_ID = 45654;

    /** @var WebsocketAuthenticationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websocketAuthenticationProvider;

    /** @var ClientStorage */
    private $clientStorage;

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $storageDriver;

    /** @var ClientEventListener */
    private $clientEventListener;

    /** @var GosClientEventListener|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedClientEventListener;

    /** @var ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var ClientEvent */
    private $clientEvent;

    protected function setUp()
    {
        $this->websocketAuthenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        // We cannot properly mock ClientStorageInterface because of static method getStorageId().
        $this->clientStorage = new ClientStorage(100, $this->createMock(LoggerInterface::class));
        $this->storageDriver = $this->createMock(DriverInterface::class);
        $this->clientStorage->setStorageDriver($this->storageDriver);

        $this->decoratedClientEventListener = $this->createMock(GosClientEventListener::class);

        $this->clientEventListener = new ClientEventListener(
            $this->decoratedClientEventListener,
            $this->websocketAuthenticationProvider,
            $this->clientStorage
        );

        $this->setUpLoggerMock($this->clientEventListener);

        $this->connection = $this->createConnection();
        $this->clientEvent = new ClientEvent($this->connection, ClientEvent::CONNECTED);
    }

    public function testOnClientConnectAuthenticationFailed(): void
    {
        $this->websocketAuthenticationProvider
            ->expects(self::once())
            ->method('authenticate')
            ->with($this->connection)
            ->willThrowException(new BadCredentialsException());

        $this->expectException(BadCredentialsException::class);

        $this->clientEventListener->onClientConnect($this->clientEvent);

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

        $this->storageDriver
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $this->expectException(StorageException::class);

        $this->clientEventListener->onClientConnect($this->clientEvent);

        self::assertNull($this->connection->WAMP->clientStorageId);
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

        $this->clientEventListener->onClientConnect($this->clientEvent);

        self::assertEquals(self::CONNECTION_RESOURCE_ID, $this->connection->WAMP->clientStorageId);
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

        $this->clientEventListener->onClientConnect($this->clientEvent);

        self::assertEquals(self::CONNECTION_RESOURCE_ID, $this->connection->WAMP->clientStorageId);
        self::assertEquals($user, $this->connection->WAMP->username);
    }

    public function testOnClientDisconnect(): void
    {
        $this->decoratedClientEventListener
            ->expects(self::once())
            ->method('onClientDisconnect')
            ->with($this->clientEvent);

        $this->clientEventListener->onClientDisconnect($this->clientEvent);
    }

    public function testOnClientRejected(): void
    {
        $clientRejectedEvent = new ClientRejectedEvent('sampleOrigin');

        $this->decoratedClientEventListener
            ->expects(self::once())
            ->method('onClientRejected')
            ->with($clientRejectedEvent);

        $this->clientEventListener->onClientRejected($clientRejectedEvent);
    }

    public function testOnClientErrorGeneralException(): void
    {
        $clientErrorEvent = new ClientErrorEvent($this->connection, ClientEvent::ERROR);
        $clientErrorEvent->setException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $this->clientEventListener->onClientError($clientErrorEvent);
    }

    public function testOnClientErrorBadCredentialsException(): void
    {
        $clientErrorEvent = new ClientErrorEvent($this->connection, ClientEvent::ERROR);
        $clientErrorEvent->setException(new BadCredentialsException());

        $this->decoratedClientEventListener
            ->expects(self::never())
            ->method('onClientError');

        $closingResponse = new Response(403);
        $this->connection
            ->expects(self::once())
            ->method('send')
            ->with((string) $closingResponse);
        $this->connection
            ->expects(self::once())
            ->method('close');

        $this->assertLoggerInfoMethodCalled();

        $this->clientEventListener->onClientError($clientErrorEvent);

        self::assertTrue($clientErrorEvent->isPropagationStopped());
    }

    /**
     * @return ConnectionInterface
     */
    private function createConnection(): ConnectionInterface
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = (object) ['sessionId' => '12345', 'prefixes' => []];
        $connection->remoteAddress = 'localhost';
        $connection->resourceId = self::CONNECTION_RESOURCE_ID;

        return $connection;
    }
}

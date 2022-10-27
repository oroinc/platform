<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use GuzzleHttp\Psr7\Request;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\ClientManipulator;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ClientManipulatorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const CONNECTION_RESOURCE_ID = '45654';
    private const USERNAME = 'sampleUsername';
    private const CLIENT_STORAGE_TTL = 100;

    /** @var ClientManipulatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedClientManipulator;

    /** @var ClientStorage */
    private $clientStorage;

    /** @var UserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $userProvider;

    /** @var TicketProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ticketProvider;

    /** @var WebsocketAuthenticationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websocketAuthenticationProvider;

    /** @var ClientManipulator */
    private $clientManipulator;

    protected function setUp(): void
    {
        $this->decoratedClientManipulator = $this->createMock(ClientManipulatorInterface::class);
        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->userProvider = $this->createMock(UserProvider::class);
        $this->ticketProvider = $this->createMock(TicketProviderInterface::class);
        $this->websocketAuthenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->clientManipulator = new ClientManipulator(
            $this->decoratedClientManipulator,
            $this->clientStorage,
            $this->userProvider,
            $this->ticketProvider,
            $this->websocketAuthenticationProvider
        );

        $this->setUpLoggerMock($this->clientManipulator);
    }

    public function testGetClientStorageException(): void
    {
        $connection = $this->createConnection();

        $this->clientStorage->expects(self::any())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn(self::CONNECTION_RESOURCE_ID);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->with(self::CONNECTION_RESOURCE_ID)
            ->willThrowException(new StorageException());

        $this->expectException(StorageException::class);

        $this->assertLoggerErrorMethodCalled();

        $this->clientManipulator->getClient($connection);
    }

    public function testGetClientNotFoundExceptionAndCannotRenew(): void
    {
        $connection = $this->createConnection();

        $this->clientStorage->expects(self::any())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn(self::CONNECTION_RESOURCE_ID);
        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->with(self::CONNECTION_RESOURCE_ID)
            ->willThrowException(new ClientNotFoundException());

        $this->expectException(ClientNotFoundException::class);

        $this->assertLoggerDebugMethodCalled();
        $this->assertLoggerErrorMethodCalled();

        $this->clientManipulator->getClient($this->createConnection());
    }

    public function testGetClientCanRenewAnonymous(): void
    {
        $connection = $this->createConnection();
        $connection->WAMP->username = self::USERNAME;
        $connection->httpRequest = new Request('GET', '/test/?ticket=abc');
        $token = new AnonymousTicketToken(
            'sampleTicketDigest',
            AuthenticationProviderInterface::USERNAME_NONE_PROVIDED
        );

        $this->clientStorage->expects(self::any())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn(self::CONNECTION_RESOURCE_ID);

        $call = 0;
        $this->clientStorage->expects(self::exactly(2))
            ->method('getClient')
            ->with(self::CONNECTION_RESOURCE_ID)
            ->willReturnCallback(static function () use (&$call, $token) {
                if ($call === 0) {
                    $call++;
                    throw new ClientNotFoundException();
                }

                return $token;
            });

        $this->userProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->with(self::USERNAME)
            ->willThrowException(new UsernameNotFoundException());

        $this->ticketProvider->expects($this->once())
            ->method('generateTicket')
            ->with(null)
            ->willReturn('new_ticket');

        $this->websocketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($token);
        $this->clientStorage->expects(self::once())
            ->method('addClient')
            ->with(self::CONNECTION_RESOURCE_ID, $token);

        $this->assertLoggerDebugMethodCalled();
        self::assertEquals($token, $this->clientManipulator->getClient($connection));
        self::assertNotEquals('ticket=abc', $connection->httpRequest->getUri()->getQuery());
    }

    public function testGetUserCanRenew(): void
    {
        $connection = $this->createConnection();
        $connection->WAMP->username = self::USERNAME;
        $connection->httpRequest = new Request('GET', '/test/?ticket=abc');
        $user = $this->createMock(UserInterface::class);
        $token = new TicketToken($user, 'credentials', 'providerKey');

        $this->clientStorage->expects(self::any())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn(self::CONNECTION_RESOURCE_ID);

        $call = 0;
        $this->clientStorage->expects(self::exactly(2))
            ->method('getClient')
            ->with(self::CONNECTION_RESOURCE_ID)
            ->willReturnCallback(static function () use (&$call, $token) {
                if ($call === 0) {
                    $call++;
                    throw new ClientNotFoundException();
                }

                return $token;
            });

        $this->userProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->with(self::USERNAME)
            ->willReturn($user);

        $this->ticketProvider->expects($this->once())
            ->method('generateTicket')
            ->with($user)
            ->willReturn('new_ticket');

        $this->websocketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($token);
        $this->clientStorage->expects(self::once())
            ->method('addClient')
            ->with(self::CONNECTION_RESOURCE_ID, $token);

        $this->assertLoggerDebugMethodCalled();
        self::assertEquals($user, $this->clientManipulator->getUser($connection));
        self::assertNotEquals('ticket=abc', $connection->httpRequest->getUri()->getQuery());
    }

    public function testGetAll()
    {
        $anonymous = false;
        $topic = $this->createMock(Topic::class);

        $expectedResult = [new \stdClass(), new \stdClass()];
        $this->decoratedClientManipulator->expects(self::once())
            ->method('getAll')
            ->with($topic, $anonymous)
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->clientManipulator->getAll($topic, $anonymous));
    }

    public function testFindByRoles()
    {
        $roles = ['sampleRole'];
        $topic = $this->createMock(Topic::class);

        $expectedResult = [new \stdClass(), new \stdClass()];
        $this->decoratedClientManipulator->expects(self::once())
            ->method('findByRoles')
            ->with($topic, $roles)
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->clientManipulator->findByRoles($topic, $roles));
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

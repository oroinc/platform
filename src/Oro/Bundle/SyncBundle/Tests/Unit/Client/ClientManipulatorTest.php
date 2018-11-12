<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Oro\Bundle\SyncBundle\Client\ClientManipulator;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ClientManipulatorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const CONNECTION_RESOURCE_ID = 45654;
    private const USERNAME = 'sampleUsername';
    private const CLIENT_STORAGE_TTL = 100;

    /**
     * @var ClientManipulatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $decoratedClientManipulator;

    /**
     * @var ClientStorage
     */
    private $clientStorage;

    /**
     * @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storageDriver;

    /**
     * @var UserProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userProvider;

    /**
     * @var ClientManipulator
     */
    private $clientManipulator;

    protected function setUp()
    {
        $this->decoratedClientManipulator = $this->createMock(ClientManipulatorInterface::class);

        // We cannot properly mock ClientStorageInterface because of static method getStorageId().
        $this->clientStorage = new ClientStorage(self::CLIENT_STORAGE_TTL, $this->createMock(LoggerInterface::class));
        $this->storageDriver = $this->createMock(DriverInterface::class);
        $this->clientStorage->setStorageDriver($this->storageDriver);

        $this->userProvider = $this->createMock(UserProvider::class);

        $this->clientManipulator = new ClientManipulator(
            $this->decoratedClientManipulator,
            $this->clientStorage,
            $this->userProvider
        );

        $this->setUpLoggerMock($this->clientManipulator);
    }

    public function testGetClientStorageException(): void
    {
        $this->storageDriver
            ->expects(self::once())
            ->method('fetch')
            ->with(self::CONNECTION_RESOURCE_ID)
            ->willThrowException(new \Exception());

        $this->expectException(StorageException::class);

        $this->assertLoggerErrorMethodCalled();

        $this->clientManipulator->getClient($this->createConnection());
    }

    public function testGetClientNotFoundExceptionAndCannotRenew(): void
    {
        $this->storageDriver
            ->expects(self::once())
            ->method('fetch')
            ->with(self::CONNECTION_RESOURCE_ID)
            ->willReturn(false);

        $this->expectException(ClientNotFoundException::class);

        $this->assertLoggerDebugMethodCalled();
        $this->assertLoggerErrorMethodCalled();

        $this->clientManipulator->getClient($this->createConnection());
    }

    public function testGetClientCanRenewAnonymous(): void
    {
        $this->storageDriver
            ->expects(self::exactly(2))
            ->method('fetch')
            ->withConsecutive([self::CONNECTION_RESOURCE_ID], [self::CONNECTION_RESOURCE_ID])
            ->willReturnOnConsecutiveCalls(false, serialize(self::USERNAME));

        $this->assertLoggerDebugMethodCalled();

        $connection = $this->createConnection();
        $connection->WAMP->username = self::USERNAME;

        $this->userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willThrowException(new UsernameNotFoundException());

        $this->storageDriver
            ->expects(self::once())
            ->method('save')
            ->with(self::CONNECTION_RESOURCE_ID, serialize(self::USERNAME), self::CLIENT_STORAGE_TTL)
            ->willReturn(true);

        self::assertEquals(self::USERNAME, $this->clientManipulator->getClient($connection));
    }

    public function testGetClientCanRenew(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->storageDriver
            ->expects(self::exactly(2))
            ->method('fetch')
            ->withConsecutive([self::CONNECTION_RESOURCE_ID], [self::CONNECTION_RESOURCE_ID])
            ->willReturnOnConsecutiveCalls(false, serialize($user));

        $this->assertLoggerDebugMethodCalled();

        $connection = $this->createConnection();
        $connection->WAMP->username = self::USERNAME;

        $this->userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $this->storageDriver
            ->expects(self::once())
            ->method('save')
            ->with(self::CONNECTION_RESOURCE_ID, serialize($user), self::CLIENT_STORAGE_TTL)
            ->willReturn(true);

        self::assertEquals($user, $this->clientManipulator->getClient($connection));
    }

    public function testFindByUserName()
    {
        $username = self::USERNAME;
        /** @var Topic $topic */
        $topic = $this->createMock(Topic::class);

        $expectedResult = ['connection' => new \stdClass(), 'user' => new \stdClass()];
        $this->decoratedClientManipulator
            ->expects(self::once())
            ->method('findByUsername')
            ->with($topic, $username)
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->clientManipulator->findByUsername($topic, $username));
    }

    public function testGetAll()
    {
        $anonymous = false;
        /** @var Topic $topic */
        $topic = $this->createMock(Topic::class);

        $expectedResult = [new \stdClass(), new \stdClass()];
        $this->decoratedClientManipulator
            ->expects(self::once())
            ->method('getAll')
            ->with($topic, $anonymous)
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->clientManipulator->getAll($topic, $anonymous));
    }

    public function testFindByRoles()
    {
        $roles = ['sampleRole'];
        /** @var Topic $topic */
        $topic = $this->createMock(Topic::class);

        $expectedResult = [new \stdClass(), new \stdClass()];
        $this->decoratedClientManipulator
            ->expects(self::once())
            ->method('findByRoles')
            ->with($topic, $roles)
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->clientManipulator->findByRoles($topic, $roles));
    }

    /**
     * @return ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject
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

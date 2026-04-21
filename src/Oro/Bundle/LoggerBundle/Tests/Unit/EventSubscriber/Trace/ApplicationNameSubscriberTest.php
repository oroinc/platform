<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\EventSubscriber\Trace;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events as DBALEvents;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\LoggerBundle\Event\SetAppNameEvent;
use Oro\Bundle\LoggerBundle\EventSubscriber\Trace\ApplicationNameSubscriber;
use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ApplicationNameSubscriberTest extends TestCase
{
    private TraceManager $traceManager;
    private ManagerRegistry&MockObject $doctrine;
    private LoggerInterface&MockObject $logger;
    private ApplicationNameSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->traceManager = new TraceManager($eventDispatcher);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new ApplicationNameSubscriber(
            $this->traceManager,
            $this->doctrine,
            $this->logger
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                DBALEvents::postConnect => [['onPostConnect', 100]],
                SetAppNameEvent::class => 'onTraceSet',
            ],
            ApplicationNameSubscriber::getSubscribedEvents()
        );
    }

    public function testOnPostConnectWhenTraceIsNull(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())
            ->method('getDatabasePlatform');
        $connection->expects(self::never())
            ->method('executeStatement');

        $event = new ConnectionEventArgs($connection);

        $this->subscriber->onPostConnect($event);
    }

    public function testOnPostConnectWithPostgreSQLSetsApplicationName(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $connection = $this->createConnectionMock();
        $connection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");

        $connection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'");

        $event = new ConnectionEventArgs($connection);

        $this->subscriber->onPostConnect($event);
    }

    public function testOnPostConnectWithMySQLDoesNotSetApplicationName(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $connection = $this->createMySQLConnectionMock();
        $connection->expects(self::never())
            ->method('executeStatement');

        $event = new ConnectionEventArgs($connection);

        $this->subscriber->onPostConnect($event);
    }

    public function testOnPostConnectReusesStaticAppName(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $firstConnection = $this->createConnectionMock();
        $firstConnection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");
        $firstConnection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'");

        $firstEvent = new ConnectionEventArgs($firstConnection);
        $this->subscriber->onPostConnect($firstEvent);

        $secondConnection = $this->createConnectionMock();
        $secondConnection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");
        $secondConnection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'");

        $secondEvent = new ConnectionEventArgs($secondConnection);
        $this->subscriber->onPostConnect($secondEvent);
    }

    public function testOnPostConnectHandlesException(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $connection = $this->createConnectionMock();

        $connection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");

        $connection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'")
            ->willThrowException(new \Exception('Database error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to set PostgreSQL application_name',
                ['exception' => 'Database error', 'traceId' => $trace]
            );

        $event = new ConnectionEventArgs($connection);

        $this->subscriber->onPostConnect($event);
    }

    public function testOnTraceSetUpdatesAllConnectedConnections(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $firstConnection = $this->createConnectionMock();
        $firstConnection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");
        $firstConnection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'");

        $secondConnection = $this->createConnectionMock(isConnected: false);
        $secondConnection->expects(self::never())
            ->method('executeStatement');

        $connection3 = $this->createConnectionMock();
        $connection3->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");
        $connection3->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'");

        $this->doctrine->expects(self::once())
            ->method('getConnections')
            ->willReturn([
                'default' => $firstConnection,
                'customer' => $secondConnection,
                'search' => $connection3,
            ]);

        $this->subscriber->onTraceSet();
    }

    public function testOnTraceSetHandlesExceptionForOneConnection(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $firstConnection = $this->createConnectionMock();
        $firstConnection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");
        $firstConnection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'")
            ->willThrowException(new \Exception('Connection error'));

        $secondConnection = $this->createConnectionMock();
        $secondConnection->expects(self::once())
            ->method('quote')
            ->with($trace)
            ->willReturn("'$trace'");
        $secondConnection->expects(self::once())
            ->method('executeStatement')
            ->with("SET application_name TO '$trace'");

        $this->doctrine->expects(self::once())
            ->method('getConnections')
            ->willReturn([
                'default' => $firstConnection,
                'second' => $secondConnection,
            ]);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to set PostgreSQL application_name',
                ['exception' => 'Connection error', 'traceId' => $trace]
            );

        $this->subscriber->onTraceSet();
    }

    private function createConnectionMock(bool $isConnected = true): Connection&MockObject
    {
        $platform = $this->createMock(PostgreSQLPlatform::class);
        $platform->method('getName')->willReturn(DatabasePlatformInterface::DATABASE_POSTGRESQL);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('isConnected')->willReturn($isConnected);

        return $connection;
    }

    private function createMySQLConnectionMock(): Connection&MockObject
    {
        $platform = $this->createMock(MySQL80Platform::class);
        $platform->method('getName')->willReturn('mysql');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        return $connection;
    }
}

<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrinePingConnectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var DoctrinePingConnectionExtension */
    private $extension;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->extension = new DoctrinePingConnectionExtension($this->container);
    }

    public function testShouldGetDoctrineRegistryFromContainerAndSaveItToProperty()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('ping')
            ->willReturn(true);

        $this->container->expects(self::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);

        $this->doctrine->expects(self::exactly(2))
            ->method('getConnectionNames')
            ->willReturn(['connection-name' => 'connection_service_id']);
        $this->doctrine->expects(self::exactly(2))
            ->method('getConnection')
            ->with('connection-name')
            ->willReturn($connection);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));

        $this->extension->onPreReceived($context);
        $this->extension->onPreReceived($context);
    }

    public function testShouldGetDoctrineRegistryFromContainerAgainAfterReset()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('ping')
            ->willReturn(true);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);

        $this->doctrine->expects(self::exactly(2))
            ->method('getConnectionNames')
            ->willReturn(['connection-name' => 'connection_service_id']);
        $this->doctrine->expects(self::exactly(2))
            ->method('getConnection')
            ->with('connection-name')
            ->willReturn($connection);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));

        $this->extension->onPreReceived($context);

        $this->extension->reset();
        $this->extension->onPreReceived($context);
    }

    public function testShouldNotReconnectIfConnectionIsOK()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('ping')
            ->willReturn(true);
        $connection->expects(self::never())
            ->method('close');
        $connection->expects(self::never())
            ->method('connect');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('debug');

        $this->container->expects(self::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);

        $this->doctrine->expects(self::once())
            ->method('getConnectionNames')
            ->willReturn(['connection-name' => 'connection_service_id']);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with('connection-name')
            ->willReturn($connection);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);

        $this->extension->onPreReceived($context);
    }

    public function testShouldDoesReconnectIfConnectionFailed()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('ping')
            ->willReturn(false);
        $connection->expects(self::once())
            ->method('close');
        $connection->expects(self::once())
            ->method('connect');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::at(0))
            ->method('debug')
            ->with('Connection "connection-name" is not active, trying to reconnect.');
        $logger->expects(self::at(1))
            ->method('debug')
            ->with('Connection "connection-name" is active now.');

        $this->container->expects(self::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);

        $this->doctrine->expects(self::once())
            ->method('getConnectionNames')
            ->willReturn(['connection-name' => 'connection_service_id']);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with('connection-name')
            ->willReturn($connection);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);

        $this->extension->onPreReceived($context);
    }
}

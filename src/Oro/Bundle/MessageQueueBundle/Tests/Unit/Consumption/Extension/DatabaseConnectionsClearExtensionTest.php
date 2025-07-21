<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DatabaseConnectionsClearExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatabaseConnectionsClearExtensionTest extends TestCase
{
    private array $connections = [
        'default' => 'default.connection.service',
        'session' => 'session.connection.service',
        'config' => 'config.connection.service',
    ];

    private ContainerInterface&MockObject $container;
    private DatabaseConnectionsClearExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->extension = new DatabaseConnectionsClearExtension($this->container, $this->connections);
    }

    public function testOnStart(): void
    {
        $this->container->expects($this->exactly(3))
            ->method('initialized')
            ->withConsecutive(
                ['default.connection.service'],
                ['session.connection.service'],
                ['config.connection.service']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            );

        $sessionConnection = $this->createMock(Connection::class);
        $sessionConnection->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);
        $sessionConnection->expects($this->never())
            ->method('close');
        $configConnection = $this->createMock(Connection::class);
        $configConnection->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);
        $configConnection->expects($this->once())
            ->method('close');

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['session.connection.service'],
                ['config.connection.service']
            )
            ->willReturnOnConsecutiveCalls(
                $sessionConnection,
                $configConnection
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('info');

        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setLogger($logger);

        $this->extension->onStart($context);
    }

    public function testOnPostReceived(): void
    {
        $this->container->expects($this->exactly(3))
            ->method('initialized')
            ->withConsecutive(
                ['default.connection.service'],
                ['session.connection.service'],
                ['config.connection.service']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            );

        $sessionConnection = $this->createMock(Connection::class);
        $sessionConnection->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);
        $sessionConnection->expects($this->never())
            ->method('close');
        $configConnection = $this->createMock(Connection::class);
        $configConnection->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);
        $configConnection->expects($this->once())
            ->method('close');

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['session.connection.service'],
                ['config.connection.service']
            )
            ->willReturnOnConsecutiveCalls(
                $sessionConnection,
                $configConnection
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Close database connections.', ['connections' => ['config']]);

        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setLogger($logger);

        $this->extension->onPostReceived($context);
    }
}

<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DoctrinePingConnectionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    private $doctrine;

    /** @var CreateQueueExtension */
    private $extension;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(RegistryInterface::class);

        $this->extension = new DoctrinePingConnectionExtension($this->doctrine);
    }

    public function testShouldNotReconnectIfConnectionIsOK()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('ping')
            ->willReturn(true);
        $connection->expects($this->never())
            ->method('close');
        $connection->expects($this->never())
            ->method('connect');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('debug');

        $this->doctrine->expects($this->once())
            ->method('getConnectionNames')
            ->willReturn(['connection-name' => 'connection_service_id']);
        $this->doctrine->expects($this->once())
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
        $connection->expects($this->once())
            ->method('ping')
            ->willReturn(false);
        $connection->expects($this->once())
            ->method('close');
        $connection->expects($this->once())
            ->method('connect');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->at(0))
            ->method('debug')
            ->with('[DoctrinePingConnectionExtension] Connection is not active trying to reconnect.');
        $logger->expects($this->at(1))
            ->method('debug')
            ->with('[DoctrinePingConnectionExtension] Connection is active now.');

        $this->doctrine->expects($this->once())
            ->method('getConnectionNames')
            ->willReturn(['connection-name' => 'connection_service_id']);
        $this->doctrine->expects($this->once())
            ->method('getConnection')
            ->with('connection-name')
            ->willReturn($connection);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $this->extension->onPreReceived($context);
    }
}

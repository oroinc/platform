<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Dbal\Extension;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Psr\Log\LoggerInterface;

class RedeliverOrphanMessagesDbalExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArgument()
    {
        new RedeliverOrphanMessagesDbalExtension();
    }

    public function testShouldRedeliverOrphanMessages()
    {
        $dbal = $this->createDBALConnection();
        $dbal
            ->expects($this->once())
            ->method('executeUpdate')
            ->with('UPDATE tableName SET consumer_id=NULL, delivered_at=NULL, redelivered=:isRedelivered '.
                'WHERE delivered_at <= :deliveredAt AND consumer_id IS NOT NULL')
            ->will($this->returnValue(3));

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->will($this->returnValue($dbal));
        $connection
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('tableName'));

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('alert')
            ->with('[RedeliverOrphanMessagesDbalExtension] Orphans were found and redelivered. number: 3');

        $consumer = $this->createMock(DbalMessageConsumer::class);

        $context = new Context($session);
        $context->setLogger($logger);
        $context->setMessageConsumer($consumer);

        $extension = new RedeliverOrphanMessagesDbalExtension();
        $extension->onBeforeReceive($context);
    }

    public function testShouldDoNothingIfNotInstanceOfDbalSession()
    {
        $session = $this->createNullSessionMock();

        $context = new Context($session);

        $extension = new RedeliverOrphanMessagesDbalExtension();
        $extension->onBeforeReceive($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createNullSessionMock()
    {
        return $this->createMock(NullSession::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createSessionMock()
    {
        return $this->createMock(DbalSession::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalConnection
     */
    private function createConnectionMock()
    {
        return $this->createMock(DbalConnection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createDBALConnection()
    {
        return $this->createMock(Connection::class);
    }
}

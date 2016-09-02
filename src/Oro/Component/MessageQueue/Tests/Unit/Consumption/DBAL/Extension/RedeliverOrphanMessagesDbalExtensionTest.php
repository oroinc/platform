<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Dbal\Extension;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
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
                'WHERE delivered_at <= :deliveredAt')
            ->will($this->returnValue(3))
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->will($this->returnValue($dbal))
        ;
        $connection
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('tableName'))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('alert')
            ->with('[RedeliverOrphanMessagesDbalExtension] Orphans were found and redelivered. number: 3')
        ;

        $context = new Context($session);
        $context->setLogger($logger);

        $extension = new RedeliverOrphanMessagesDbalExtension();
        $extension->onBeforeReceive($context);
    }

    public function testShouldDoNothingIfNotInstanceOfDbalSession()
    {
        $session = $this->createNullSessionMock();
        $session
            ->expects($this->never())
            ->method('getConnection')
        ;

        $context = new Context($session);

        $extension = new RedeliverOrphanMessagesDbalExtension();
        $extension->onBeforeReceive($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createNullSessionMock()
    {
        return $this->getMock(NullSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createSessionMock()
    {
        return $this->getMock(DbalSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalConnection
     */
    private function createConnectionMock()
    {
        return $this->getMock(DbalConnection::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createDBALConnection()
    {
        return $this->getMock(Connection::class, [], [], '', false);
    }
}

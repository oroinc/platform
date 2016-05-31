<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\DoctrineHeartbeatExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessor;
use Oro\Component\MessageQueue\Transport\MessageConsumer;
use Oro\Component\MessageQueue\Transport\Session;
use Psr\Log\LoggerInterface;

class DoctrineHeartbeatExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new DoctrineHeartbeatExtension([]);
    }

    public function testShouldThrowExceptionIfGotUnsupportedConnectionType()
    {
        $this->setExpectedException(\LogicException::class, 'Got unsupported Connection instance. "stdClass"');

        $registry = $this->createManagerRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnections')
            ->will($this->returnValue([new \stdClass()]))
        ;

        $context = $this->createContext();

        $extension = new DoctrineHeartbeatExtension([$registry]);
        $extension->onPreReceived($context);
    }

    public function testShouldNotReconnectIfConnectionIsOK()
    {
        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('ping')
            ->will($this->returnValue(true))
        ;
        $connection
            ->expects($this->never())
            ->method('close')
        ;
        $connection
            ->expects($this->never())
            ->method('connect')
        ;

        $registry = $this->createManagerRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnections')
            ->will($this->returnValue([$connection]))
        ;

        $context = $this->createContext();

        $extension = new DoctrineHeartbeatExtension([$registry]);
        $extension->onPreReceived($context);
    }

    public function testShouldDoesReconnectIfConnectionFailed()
    {
        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('ping')
            ->will($this->returnValue(false))
        ;
        $connection
            ->expects($this->once())
            ->method('close')
        ;
        $connection
            ->expects($this->once())
            ->method('connect')
        ;

        $registry = $this->createManagerRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnections')
            ->will($this->returnValue([$connection]))
        ;

        $context = $this->createContext();

        $extension = new DoctrineHeartbeatExtension([$registry]);
        $extension->onPreReceived($context);
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        return new Context(
            $this->getMock(Session::class),
            $this->getMock(MessageConsumer::class),
            $this->getMock(MessageProcessor::class),
            $this->getMock(LoggerInterface::class)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected function createManagerRegistryMock()
    {
        return $this->getMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    protected function createConnectionMock()
    {
        return $this->getMock(Connection::class, [], [], '', false);
    }
}

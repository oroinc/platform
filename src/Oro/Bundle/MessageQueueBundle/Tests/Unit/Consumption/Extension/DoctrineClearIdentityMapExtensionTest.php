<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrineClearIdentityMapExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DoctrineClearIdentityMapExtension($this->createRegistryMock());
    }

    public function testShouldClearIdentityMap()
    {
        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('clear')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getManagers')
            ->will($this->returnValue([$manager]))
        ;

        $extension = new DoctrineClearIdentityMapExtension($registry);
        $extension->onPreReceived($this->createContext());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        return new Context(
            $this->getMock(SessionInterface::class),
            $this->getMock(MessageConsumerInterface::class),
            $this->getMock(MessageProcessorInterface::class),
            $this->getMock(LoggerInterface::class)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createRegistryMock()
    {
        return $this->getMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected function createManagerMock()
    {
        return $this->getMock(ObjectManager::class);
    }
}

<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineClearIdentityMapExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var DoctrineClearIdentityMapExtension */
    private $extension;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->extension = new DoctrineClearIdentityMapExtension($this->container);
    }

    public function testShouldGetDoctrineRegistryFromContainerAndSaveItToProperty()
    {
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerNames')
            ->willReturn(['manager' => 'manager_service_id']);

        $this->container->expects(self::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);
        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('manager_service_id')
            ->willReturn(false);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));

        $this->extension->onPostReceived($context);
        $this->extension->onPostReceived($context);
    }

    public function testShouldGetDoctrineRegistryFromContainerAgainAfterReset()
    {
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerNames')
            ->willReturn(['manager' => 'manager_service_id']);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);
        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('manager_service_id')
            ->willReturn(false);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));

        $this->extension->onPostReceived($context);

        $this->extension->reset();
        $this->extension->onPostReceived($context);
    }

    public function testOnPostReceivedShouldClearIdentityMapForInitializedEntityManagers()
    {
        $manager1 = $this->createMock(ObjectManager::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerNames')
            ->willReturn(['manager1' => 'manager1_service_id', 'manager2' => 'manager2_service_id']);
        $this->doctrine->expects(self::once())
            ->method('getManager')
            ->with('manager1')
            ->willReturn($manager1);

        $this->container->expects(self::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($this->doctrine);
        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->willReturnMap([
                ['manager1_service_id', true],
                ['manager2_service_id', false],
            ]);

        $logger->expects(self::once())
            ->method('debug')
            ->with('Clear identity map for manager "manager1"');
        $manager1->expects(self::once())
            ->method('clear');

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);

        $this->extension->onPostReceived($context);
    }
}

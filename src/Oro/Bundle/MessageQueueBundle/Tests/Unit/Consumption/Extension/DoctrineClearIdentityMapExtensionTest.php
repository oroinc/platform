<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DoctrineClearIdentityMapExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|RegistryInterface */
    private $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject|IntrospectableContainerInterface */
    private $container;

    /** @var DoctrineClearIdentityMapExtension */
    private $extension;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(RegistryInterface::class);
        $this->container = $this->createMock(IntrospectableContainerInterface::class);

        $this->extension = new DoctrineClearIdentityMapExtension($this->doctrine);
        $this->extension->setContainer($this->container);
    }

    public function testOnPostReceivedShouldClearIdentityMapForInitializedEntityManagers()
    {
        $manager1 = $this->createMock(ObjectManager::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['manager1' => 'manager1_service_id', 'manager2' => 'manager2_service_id']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('manager1')
            ->willReturn($manager1);

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->willReturnMap([
                ['manager1_service_id', true],
                ['manager2_service_id', false],
            ]);

        $logger->expects($this->once())
            ->method('debug')
            ->with('[DoctrineClearIdentityMapExtension] Clear identity map for manager "manager1"');
        $manager1->expects($this->once())
            ->method('clear');

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $this->extension->onPostReceived($context);
    }
}

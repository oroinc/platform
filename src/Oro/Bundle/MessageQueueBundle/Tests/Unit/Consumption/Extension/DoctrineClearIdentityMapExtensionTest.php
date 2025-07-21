<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineClearIdentityMapExtensionTest extends TestCase
{
    private ContainerInterface&MockObject $container;

    private array $managers = ['default' => 'default.manager.service', 'config' => 'config.manager.service'];

    private DoctrineClearIdentityMapExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->extension = new DoctrineClearIdentityMapExtension($this->container, $this->managers);
    }

    public function testOnPostReceived(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('initialized')
            ->withConsecutive(
                ['default.manager.service'],
                ['config.manager.service']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $configManager = $this->createMock(ObjectManager::class);
        $configManager->expects($this->once())
            ->method('clear');

        $this->container->expects($this->once())
            ->method('get')
            ->with('config.manager.service')
            ->willReturn($configManager);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Clear entity managers identity map.', ['entity_managers' => ['config']]);

        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setLogger($logger);

        $this->extension->onPostReceived($context);
    }
}

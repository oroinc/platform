<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildDestinationMetaRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageToArrayConverterPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildRouteRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\ConfigureClearersPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\ConfigureDbalTransportExtensionsPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\MakeAnnotationReaderServicesPersistentPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\MakeLoggerServicesPersistentPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\OroMessageQueueBundle;
use Oro\Component\MessageQueue\DependencyInjection\DefaultTransportFactory;
use Oro\Component\MessageQueue\DependencyInjection\NullTransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class OroMessageQueueBundleTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroMessageQueueBundle */
    private $bundle;

    /** @var Kernel|\PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    protected function setUp()
    {
        $this->kernel = $this->createMock(Kernel::class);
        $this->bundle = new OroMessageQueueBundle($this->kernel);
    }

    public function testShouldRegisterExpectedCompilerPasses()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(ConfigureDbalTransportExtensionsPass::class));
        $container->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildExtensionsPass::class));
        $container->expects($this->at(2))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildRouteRegistryPass::class));
        $container->expects($this->at(3))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildMessageProcessorRegistryPass::class));
        $container->expects($this->at(4))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildTopicMetaSubscribersPass::class));
        $container->expects($this->at(5))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildDestinationMetaRegistryPass::class));
        $container->expects($this->at(6))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildMessageToArrayConverterPass::class));
        $container->expects($this->at(7))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(ConfigureClearersPass::class));
        $container->expects($this->at(8))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(MakeLoggerServicesPersistentPass::class));
        $container->expects($this->at(9))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(MakeAnnotationReaderServicesPersistentPass::class));

        $container->expects($this->once())
            ->method('getExtension')
            ->willReturn($this->createMock(OroMessageQueueExtension::class));

        $bundle = new OroMessageQueueBundle($this->kernel);
        $bundle->build($container);
    }

    public function testShouldRegisterExpectedTransportFactories()
    {
        $extension = $this->createMock(OroMessageQueueExtension::class);

        $extension->expects($this->at(0))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(DefaultTransportFactory::class));
        $extension->expects($this->at(1))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(NullTransportFactory::class));

        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtension')
            ->with('oro_message_queue')
            ->willReturn($extension);

        $bundle = new OroMessageQueueBundle($this->kernel);
        $bundle->build($container);
    }
}

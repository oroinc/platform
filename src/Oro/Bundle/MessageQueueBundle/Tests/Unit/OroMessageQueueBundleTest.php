<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildDestinationMetaRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildRouteRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\OroMessageQueueBundle;
use Oro\Component\MessageQueue\DependencyInjection\DefaultTransportFactory;
use Oro\Component\MessageQueue\DependencyInjection\NullTransportFactory;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMessageQueueBundleTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldExtendBundleClass()
    {
        $this->assertClassExtends(Bundle::class, OroMessageQueueBundle::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new OroMessageQueueBundle();
    }

    public function testShouldRegisterExpectedCompilerPasses()
    {
        $extensionMock = $this->getMock(OroMessageQueueExtension::class, [], [], '', false);

        $container = $this->getMock(ContainerBuilder::class);
        $container
            ->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildExtensionsPass::class))
        ;
        $container
            ->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildRouteRegistryPass::class))
        ;
        $container
            ->expects($this->at(2))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildMessageProcessorRegistryPass::class))
        ;
        $container
            ->expects($this->at(3))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildTopicMetaSubscribersPass::class))
        ;
        $container
            ->expects($this->at(4))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildDestinationMetaRegistryPass::class))
        ;
        $container
            ->expects($this->at(5))
            ->method('getExtension')
            ->willReturn($extensionMock)
        ;

        $bundle = new OroMessageQueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterDefaultAndNullTransportFactories()
    {
        $extensionMock = $this->getMock(OroMessageQueueExtension::class, [], [], '', false);

        $extensionMock
            ->expects($this->at(0))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(DefaultTransportFactory::class))
        ;
        $extensionMock
            ->expects($this->at(1))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(NullTransportFactory::class))
        ;

        $container = $this->getMock(ContainerBuilder::class);
        $container
            ->expects($this->at(5))
            ->method('getExtension')
            ->with('oro_message_queue')
            ->willReturn($extensionMock)
        ;

        $bundle = new OroMessageQueueBundle();
        $bundle->build($container);
    }
}

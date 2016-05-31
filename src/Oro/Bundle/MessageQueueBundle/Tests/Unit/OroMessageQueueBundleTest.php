<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildRouteRegistryPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Oro\Bundle\MessageQueueBundle\OroMessageQueueBundle;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    public function testShouldRegisterExpectedCompillerPasses()
    {
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

        $bundle = new OroMessageQueueBundle();
        $bundle->build($container);
    }
}

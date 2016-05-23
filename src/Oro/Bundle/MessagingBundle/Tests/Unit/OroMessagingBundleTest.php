<?php
namespace Oro\Bundle\MessagingBundle\Tests\Unit;

use Oro\Bundle\MessagingBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Bundle\MessagingBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Bundle\MessagingBundle\DependencyInjection\Compiler\BuildRouteRegistryPass;
use Oro\Bundle\MessagingBundle\OroMessagingBundle;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMessagingBundleTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldExtendBundleClass()
    {
        $this->assertClassExtends(Bundle::class, OroMessagingBundle::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new OroMessagingBundle();
    }

    public function testShouldRegisterExpectedCompillerPasses()
    {
        $container = $this->getMock(ContainerBuilder::class);
        $container
            ->expects($this->exactly(3))
            ->method('addCompilerPass')
        ;
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

        $bundle = new OroMessagingBundle();
        $bundle->build($container);
    }
}
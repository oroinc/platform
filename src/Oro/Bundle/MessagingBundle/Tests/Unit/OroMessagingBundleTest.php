<?php
namespace Oro\Bundle\MessagingBundle\Tests\Unit;

use Oro\Bundle\MessagingBundle\DependencyInjection\Compiler\BuildExtensionsPass;
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

    public function testShouldRegisterBuildExtensionsComplilerPass()
    {
        $container = $this->getMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildExtensionsPass::class))
        ;

        $bundle = new OroMessagingBundle();
        $bundle->build($container);
    }
}
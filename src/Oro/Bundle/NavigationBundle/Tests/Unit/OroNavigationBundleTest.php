<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit;

use Oro\Bundle\NavigationBundle\OroNavigationBundle;

class OroNavigationBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    'Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderChainPass'
                )
            );
        $container->expects($this->at(1))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    'Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\TagGeneratorPass'
                )
            );

        $bundle = new OroNavigationBundle();
        $bundle->build($container);
    }
}

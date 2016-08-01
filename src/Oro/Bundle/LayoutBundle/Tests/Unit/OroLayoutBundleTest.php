<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Bundle\LayoutBundle\OroLayoutBundle;

class OroLayoutBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass'));
        $container->expects($this->at(1))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf('Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ExpressionCompilerPass')
            );

        $bundle = new OroLayoutBundle();
        $bundle->build($container);
    }
}

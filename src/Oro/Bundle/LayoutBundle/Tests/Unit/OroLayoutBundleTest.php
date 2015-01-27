<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Bundle\LayoutBundle\OroLayoutBundle;

class OroLayoutBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockTypePass'));

        $bundle = new OroLayoutBundle();
        $bundle->build($container);
    }
}

<?php
namespace Oro\Bundle\ActivityBundle\Tests\Unit;

use Oro\Bundle\ActivityBundle\OroActivityBundle;

class OroActivityBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $bundle = new OroActivityBundle();

        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf('Oro\Bundle\ActivityBundle\DependencyInjection\Compiler\ActivityWidgetProviderPass')
            );

        $bundle->build($container);
    }
}

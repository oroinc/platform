<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit;

use Oro\Bundle\EmbeddedFormBundle\OroEmbeddedFormBundle;

class OroEmbeddedFormBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldAddEmbeddedFormCompilerPassDuringBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $bundle = new OroEmbeddedFormBundle();

        $container->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass'));

        $bundle->build($container);
    }
}

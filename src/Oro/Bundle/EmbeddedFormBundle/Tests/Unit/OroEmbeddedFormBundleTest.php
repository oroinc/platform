<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit;

use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass;
use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\LayoutManagerPass;
use Oro\Bundle\EmbeddedFormBundle\OroEmbeddedFormBundle;

class OroEmbeddedFormBundleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldAddEmbeddedFormCompilerPassDuringBuild()
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $bundle = new OroEmbeddedFormBundle();

        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(EmbeddedFormPass::class));

        $container->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(LayoutManagerPass::class));

        $bundle->build($container);
    }
}

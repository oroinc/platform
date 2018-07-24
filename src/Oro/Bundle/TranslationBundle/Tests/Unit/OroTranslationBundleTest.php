<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationPackagesProviderPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationStrategyPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\TranslationBundle\OroTranslationBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTranslationBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $containerBuilder = $this->getContainerBuilder();
        $containerBuilder->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(TranslatorDependencyPass::class));
        $containerBuilder->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(DebugTranslatorPass::class));
        $containerBuilder->expects($this->at(2))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(TranslationContextResolverPass::class));
        $containerBuilder->expects($this->at(3))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(TranslationStrategyPass::class));
        $containerBuilder->expects($this->at(4))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(TranslationPackagesProviderPass::class));

        $bundle = new OroTranslationBundle();
        $bundle->build($containerBuilder);
    }

    /**
     * @return ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getContainerBuilder()
    {
        return $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();
    }
}

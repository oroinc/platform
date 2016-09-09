<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\AddDateTimeFormatConverterCompilerPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\CurrentLocalizationPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\OroLocaleBundle;

class OroLocaleBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroLocaleBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(4, $passes);
        $this->assertInstanceOf(AddDateTimeFormatConverterCompilerPass::class, $passes[0]);
        $this->assertInstanceOf(TwigSandboxConfigurationPass::class, $passes[1]);
        $this->assertInstanceOf(CurrentLocalizationPass::class, $passes[2]);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[3]);
        $this->assertAttributeEquals(
            [
                Localization::class => [
                    'title' => 'titles'
                ]
            ],
            'classes',
            $passes[3]
        );
    }
}

<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\AddDateTimeFormatConverterCompilerPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\CurrentLocalizationPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\PreferredLanguageProviderPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\OroLocaleBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroLocaleBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroLocaleBundle();
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $this->assertInternalType('array', $passes);
        $this->assertCount(5, $passes);
        $this->assertInstanceOf(AddDateTimeFormatConverterCompilerPass::class, $passes[0]);
        $this->assertInstanceOf(TwigSandboxConfigurationPass::class, $passes[1]);
        $this->assertInstanceOf(CurrentLocalizationPass::class, $passes[2]);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[3]);
        $this->assertInstanceOf(PreferredLanguageProviderPass::class, $passes[4]);
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

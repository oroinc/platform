<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleVotersPass;
use Oro\Bundle\FeatureToggleBundle\OroFeatureToggleBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFeatureToggleBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroFeatureToggleBundle();
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $this->assertIsArray($passes);
        $this->assertCount(2, $passes);
        $this->assertInstanceOf(FeatureToggleVotersPass::class, $passes[0]);
    }

    public function testBuildAfterRemovingPasses()
    {
        $container = new ContainerBuilder();

        $bundle = new OroFeatureToggleBundle();
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getAfterRemovingPasses();
        $passes = array_filter(
            $passes,
            static function (CompilerPassInterface $pass) {
                return strpos(get_class($pass), 'Symfony\\') !== 0;
            }
        );

        $this->assertIsArray($passes);
        $this->assertCount(1, $passes);
        $this->assertInstanceOf(ConfigurationPass::class, reset($passes));
    }
}

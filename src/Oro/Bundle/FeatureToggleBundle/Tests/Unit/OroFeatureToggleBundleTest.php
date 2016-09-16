<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleVotersPass;
use Oro\Bundle\FeatureToggleBundle\OroFeatureToggleBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFeatureToggleBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroFeatureToggleBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(2, $passes);
        $this->assertInstanceOf(FeatureToggleVotersPass::class, $passes[0]);
    }

    public function testBuildAfterRemovingPasses()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroFeatureToggleBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getAfterRemovingPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(1, $passes);
        $this->assertInstanceOf(ConfigurationPass::class, $passes[0]);
    }
}

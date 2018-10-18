<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;
use Oro\Bundle\DataGridBundle\OroDataGridBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class OroDataGridBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->createMock(KernelInterface::class);

        $container = new ContainerBuilder();

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroDataGridBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $this->assertInternalType('array', $passes);
        $this->assertCount(8, $passes);
        $this->assertInstanceOf(CompilerPass\ConfigurationPass::class, $passes[0]);
        $this->assertInstanceOf(CompilerPass\FormattersPass::class, $passes[1]);
        $this->assertInstanceOf(CompilerPass\ActionsPass::class, $passes[2]);
        $this->assertInstanceOf(CompilerPass\GuessPass::class, $passes[3]);
        $this->assertInstanceOf(CompilerPass\InlineEditColumnOptionsGuesserPass::class, $passes[4]);
        $this->assertInstanceOf(CompilerPass\SetDatagridEventListenersLazyPass::class, $passes[5]);
        $this->assertInstanceOf(CompilerPass\BoardProcessorsPass::class, $passes[6]);
        $this->assertInstanceOf(CompilerPass\SelectedFieldsProvidersPass::class, $passes[7]);
    }
}

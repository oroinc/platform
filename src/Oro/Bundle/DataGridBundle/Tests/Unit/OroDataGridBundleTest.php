<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;
use Oro\Bundle\DataGridBundle\OroDataGridBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class OroDataGridBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->createMock(KernelInterface::class);

        $container = new ContainerBuilder();

        $bundle = new OroDataGridBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(9, $passes);
        $this->assertInstanceOf(CompilerPass\ConfigurationPass::class, $passes[0]);
        $this->assertInstanceOf(CompilerPass\FormattersPass::class, $passes[1]);
        $this->assertInstanceOf(CompilerPass\ActionsPass::class, $passes[2]);
        $this->assertInstanceOf(CompilerPass\ActionProvidersPass::class, $passes[3]);
        $this->assertInstanceOf(CompilerPass\MassActionsPass::class, $passes[4]);
        $this->assertInstanceOf(CompilerPass\GuessPass::class, $passes[5]);
        $this->assertInstanceOf(CompilerPass\InlineEditColumnOptionsGuesserPass::class, $passes[6]);
        $this->assertInstanceOf(CompilerPass\SetDatagridEventListenersLazyPass::class, $passes[7]);
        $this->assertInstanceOf(CompilerPass\BoardProcessorsPass::class, $passes[8]);
    }
}

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipTreeProvidersPass;

class OwnershipTreeProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @var OwnershipTreeProvidersPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->containerBuilder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->compilerPass = new OwnershipTreeProvidersPass();
    }

    protected function tearDown()
    {
        unset($this->containerBuilder, $this->compilerPass);
    }

    public function testProcessNotRegisterChain()
    {
        $this->containerBuilder->expects($this->once())
            ->method('has')
            ->with(OwnershipTreeProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn(false);
        $this->containerBuilder->expects($this->never())
            ->method('getDefinition');
        $this->containerBuilder->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addProvider', [new Reference('treeprovider1')]);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addProvider', [new Reference('treeprovider2')]);

        $this->containerBuilder->expects($this->once())
            ->method('has')
            ->with(OwnershipTreeProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(OwnershipTreeProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn($definition);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(OwnershipTreeProvidersPass::TAG_NAME)
            ->willReturn(
                [
                    'treeProvider1' => [['class' => 'Test\Class1']],
                    'treeProvider2' => [['class' => 'Test\Class2']],
                ]
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTagged()
    {
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $definition->expects($this->never())
            ->method('addMethodCall');
        $this->containerBuilder->expects($this->once())
            ->method('has')
            ->with(OwnershipTreeProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn(true);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(OwnershipTreeProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn($definition);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(OwnershipTreeProvidersPass::TAG_NAME)
            ->willReturn([]);

        $this->compilerPass->process($this->containerBuilder);
    }
}

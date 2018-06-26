<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipTreeProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OwnershipTreeProvidersPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @var OwnershipTreeProvidersPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->containerBuilder = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
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
        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');

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
                    'treeprovider1' => [['class' => 'Test\Class1']],
                    'treeprovider2' => [['class' => 'Test\Class2']],
                ]
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTagged()
    {
        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');

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

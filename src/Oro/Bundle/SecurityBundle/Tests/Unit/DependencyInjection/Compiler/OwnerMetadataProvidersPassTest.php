<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnerMetadataProvidersPass;

class OwnerMetadataProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * @var OwnerMetadataProvidersPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new OwnerMetadataProvidersPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testProcessNotRegisterProvider()
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with(OwnerMetadataProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition');
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addProvider', [new Reference('provider1')]);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addProvider', [new Reference('provider2')]);

        $this->container->expects($this->once())
            ->method('has')
            ->with(OwnerMetadataProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(OwnerMetadataProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(OwnerMetadataProvidersPass::TAG_NAME)
            ->willReturn([
                'provider1' => [['class' => 'Test\Class1']],
                'provider2' => [['class' => 'Test\Class2']],
            ]);

        $this->compilerPass->process($this->container);
    }

    public function testProcessEmptyProviders()
    {
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->never())
            ->method('addMethodCall');

        $this->container->expects($this->once())
            ->method('has')
            ->with(OwnerMetadataProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(OwnerMetadataProvidersPass::CHAIN_SERVICE_ID)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(OwnerMetadataProvidersPass::TAG_NAME)
            ->willReturn([]);

        $this->compilerPass->process($this->container);
    }
}

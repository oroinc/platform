<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipDecisionMakerPass;

class OwnershipDecisionMakerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * @var OwnershipDecisionMakerPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->compilerPass = new OwnershipDecisionMakerPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testProcessNotRegisterOwnershipDecisionMaker()
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with(OwnershipDecisionMakerPass::CHAIN_SERVICE_ID)
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
            ->with('addOwnershipDecisionMaker', [new Reference('ownershipDecisionMaker')]);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addOwnershipDecisionMaker', [new Reference('ownershipDecisionMaker2')]);

        $this->container->expects($this->once())
            ->method('has')
            ->with(OwnershipDecisionMakerPass::CHAIN_SERVICE_ID)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(OwnershipDecisionMakerPass::CHAIN_SERVICE_ID)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(OwnershipDecisionMakerPass::TAG_NAME)
            ->willReturn([
                'ownershipDecisionMaker' => [['class' => 'Test\Class1']],
                'ownershipDecisionMaker2' => [['class' => 'Test\Class2']]
            ]);

        $this->compilerPass->process($this->container);
    }

    public function testProcessEmptyOwnershipDecisionMakers()
    {
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $definition->expects($this->never())
            ->method('addMethodCall');
        $this->container->expects($this->once())
            ->method('has')
            ->with(OwnershipDecisionMakerPass::CHAIN_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(OwnershipDecisionMakerPass::CHAIN_SERVICE_ID)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(OwnershipDecisionMakerPass::TAG_NAME)
            ->willReturn([]);

        $this->compilerPass->process($this->container);
    }
}

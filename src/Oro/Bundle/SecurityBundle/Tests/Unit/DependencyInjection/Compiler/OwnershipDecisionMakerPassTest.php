<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipDecisionMakerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class OwnershipDecisionMakerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var OwnershipDecisionMakerPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->compilerPass = new OwnershipDecisionMakerPass();
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
        $definition = $this->createMock(Definition::class);

        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addOwnershipDecisionMaker', [new Reference('ownershipDecisionMaker')]],
                ['addOwnershipDecisionMaker', [new Reference('ownershipDecisionMaker2')]]
            );

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
        $definition = $this->createMock(Definition::class);

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

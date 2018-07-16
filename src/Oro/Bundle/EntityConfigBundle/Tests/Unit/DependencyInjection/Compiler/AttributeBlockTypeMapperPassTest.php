<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AttributeBlockTypeMapperPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $registry = $this->getMockBuilder(Definition::class)->getMock();

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(AttributeBlockTypeMapperPass::CHAIN_SERVICE)
            ->willReturn($registry);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(AttributeBlockTypeMapperPass::TAG)
            ->willReturn(['service1' => [['priority' => 20]], 'service2' => [['priority' => 10]]]);

        $registry->expects($this->exactly(2))
            ->method('addMethodCall')
            ->willReturnMap([
                ['addMapper', [new Reference('service2')], $registry],
                ['addMapper', [new Reference('service1')], $registry],
            ]);

        $compilerPass = new AttributeBlockTypeMapperPass();
        $compilerPass->process($container);
    }

    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(AttributeBlockTypeMapperPass::TAG)
            ->willReturn([]);

        $container->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new AttributeBlockTypeMapperPass();
        $compilerPass->process($container);
    }
}

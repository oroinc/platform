<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockViewSerializerNormalizersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BlockViewSerializerNormalizersPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $serializerServiceDef = $this->createMock(Definition::class);
        $expressionNormalizerServiceId = 'oro_layout.block_view_serializer.expression_normalizer';
        $normalizerTags = [$expressionNormalizerServiceId => []];

        $container->expects($this->at(0))
            ->method('has')
            ->with(BlockViewSerializerNormalizersPass::BLOCK_VIEW_SERIALIZER_SERVICE_ID)
            ->willReturn(true);

        $container->expects($this->at(1))
            ->method('findTaggedServiceIds')
            ->with(BlockViewSerializerNormalizersPass::BLOCK_VIEW_SERIALIZER_NORMALIZER_TAG)
            ->will($this->returnValue($normalizerTags));

        $container->expects($this->at(2))
            ->method('findDefinition')
            ->with(BlockViewSerializerNormalizersPass::BLOCK_VIEW_SERIALIZER_SERVICE_ID)
            ->will($this->returnValue($serializerServiceDef));

        $serializerServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with(0, [new Reference($expressionNormalizerServiceId)]);

        $compilerPass = new BlockViewSerializerNormalizersPass();
        $compilerPass->process($container);
    }
}

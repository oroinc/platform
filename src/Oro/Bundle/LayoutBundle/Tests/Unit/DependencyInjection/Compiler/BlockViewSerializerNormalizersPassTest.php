<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockViewSerializerNormalizersPass;

class BlockViewSerializerNormalizersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = $this->getMock(ContainerBuilder::class);
        $serializerServiceDef = $this->getMock(Definition::class);
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

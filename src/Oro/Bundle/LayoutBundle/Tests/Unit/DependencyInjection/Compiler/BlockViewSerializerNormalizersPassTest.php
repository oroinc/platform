<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockViewSerializerNormalizersPass;
use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewVarsNormalizer;
use Oro\Bundle\LayoutBundle\Layout\Serializer\ExpressionNormalizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BlockViewSerializerNormalizersPassTest extends \PHPUnit\Framework\TestCase
{
    private BlockViewSerializerNormalizersPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BlockViewSerializerNormalizersPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $serializerDef = $container->register('oro_layout.block_view_serializer')
            ->addArgument([]);
        $typeNameConverterDef = $container->register('oro_layout.block_view_serializer.type_name_converter')
            ->addArgument([]);
        $container->setParameter('normalizer.class', ExpressionNormalizer::class);

        $container->register('normalizer_1', BlockViewVarsNormalizer::class)
            ->addTag('layout.block_view_serializer.normalizer');
        $container->register('normalizer_2', ExpressionNormalizer::class)
            ->addTag('layout.block_view_serializer.normalizer');
        $container->register('normalizer_3', '%normalizer.class%')
            ->addTag('layout.block_view_serializer.normalizer');

        $this->compiler->process($container);

        $this->assertEquals(
            [new Reference('normalizer_1'), new Reference('normalizer_2'), new Reference('normalizer_3')],
            $serializerDef->getArgument(0)
        );
        $this->assertEquals(
            [new Reference('normalizer_2'), new Reference('normalizer_3')],
            $typeNameConverterDef->getArgument(0)
        );
    }
}

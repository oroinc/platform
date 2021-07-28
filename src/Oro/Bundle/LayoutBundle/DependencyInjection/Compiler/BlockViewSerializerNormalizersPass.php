<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\Layout\Serializer\TypeNameConverterInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all block view normalizers.
 */
class BlockViewSerializerNormalizersPass implements CompilerPassInterface
{
    private const BLOCK_VIEW_SERIALIZER_SERVICE_ID = 'oro_layout.block_view_serializer';
    private const TYPE_NAME_CONVERTER_SERVICE_ID = 'oro_layout.block_view_serializer.type_name_converter';
    private const TAG_NAME = 'layout.block_view_serializer.normalizer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $normalizers = [];
        $typeNameConverters = [];
        $parameterBag = $container->getParameterBag();
        $taggedServiceIds = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServiceIds as $id => $attributes) {
            $normalizers[] = new Reference($id);
            $normalizerClass = $parameterBag->resolveValue($container->getDefinition($id)->getClass());
            if (is_subclass_of($normalizerClass, TypeNameConverterInterface::class)) {
                $typeNameConverters[] = new Reference($id);
            }
        }

        $container->findDefinition(self::BLOCK_VIEW_SERIALIZER_SERVICE_ID)
            ->replaceArgument(0, $normalizers);
        $container->findDefinition(self::TYPE_NAME_CONVERTER_SERVICE_ID)
            ->replaceArgument(0, $typeNameConverters);
    }
}

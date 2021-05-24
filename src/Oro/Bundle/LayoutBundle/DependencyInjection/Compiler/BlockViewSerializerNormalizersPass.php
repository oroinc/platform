<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all block view normalizers.
 */
class BlockViewSerializerNormalizersPass implements CompilerPassInterface
{
    private const BLOCK_VIEW_SERIALIZER_SERVICE_ID = 'oro_layout.block_view_serializer';
    private const TAG_NAME = 'layout.block_view_serializer.normalizer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $normalizers = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServiceIds as $id => $attributes) {
            $normalizers[] = new Reference($id);
        }

        $container->findDefinition(self::BLOCK_VIEW_SERIALIZER_SERVICE_ID)
            ->replaceArgument(0, $normalizers);
    }
}

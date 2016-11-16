<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BlockViewSerializerNormalizersPass implements CompilerPassInterface
{
    const BLOCK_VIEW_SERIALIZER_SERVICE_ID = 'oro_layout.block_view_serializer';
    const BLOCK_VIEW_SERIALIZER_NORMALIZER_TAG = 'layout.block_view_serializer.normalizer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::BLOCK_VIEW_SERIALIZER_SERVICE_ID)) {
            return;
        }

        $normalizers = [];
        $taggedServices = $container->findTaggedServiceIds(self::BLOCK_VIEW_SERIALIZER_NORMALIZER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $normalizers[] = new Reference($id);
        }

        $serializer = $container->findDefinition(self::BLOCK_VIEW_SERIALIZER_SERVICE_ID);
        $serializer->replaceArgument(0, $normalizers);
    }
}

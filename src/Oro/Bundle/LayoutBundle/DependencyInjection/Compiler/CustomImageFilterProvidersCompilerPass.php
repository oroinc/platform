<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all image filter providers.
 */
class CustomImageFilterProvidersCompilerPass implements CompilerPassInterface
{
    private const IMAGE_LOADER_PROVIDER_SERVICE_ID = 'oro_layout.loader.image_filter';
    private const TAG_NAME = 'layout.image_filter.provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $imageFilterDef = $container->findDefinition(self::IMAGE_LOADER_PROVIDER_SERVICE_ID);
        $taggedServiceIds = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServiceIds as $id => $attributes) {
            $imageFilterDef->addMethodCall('addCustomImageFilterProvider', [new Reference($id)]);
        }
    }
}

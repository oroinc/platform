<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomImageFilterProvidersCompilerPass implements CompilerPassInterface
{
    const IMAGE_LOADER_PROVIDER_SERVICE_ID = 'oro_layout.loader.image_filter';
    const TAG_NAME = 'layout.image_filter.provider';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::IMAGE_LOADER_PROVIDER_SERVICE_ID)) {
            return;
        }

        $definition = $container->findDefinition(self::IMAGE_LOADER_PROVIDER_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addCustomImageFilterProvider', [new Reference($id)]);
        }
    }
}

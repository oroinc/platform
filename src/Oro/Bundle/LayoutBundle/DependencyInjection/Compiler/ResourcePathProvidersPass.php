<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ResourcePathProvidersPass implements CompilerPassInterface
{
    const PROVIDER_SERVICE = 'oro_layout.loader.path_provider';
    const TAG_NAME = 'layout.resource.path_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::PROVIDER_SERVICE)) {
            $chainDef = $container->getDefinition(self::PROVIDER_SERVICE);

            foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tag) {
                $priority = isset($tag[0]['priority']) ? $tag[0]['priority'] : 0;

                $chainDef->addMethodCall('addProvider', [new Reference($serviceId), $priority]);
            }
        }
    }
}

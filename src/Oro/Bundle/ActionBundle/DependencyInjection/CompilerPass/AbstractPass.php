<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Provides common functionality for compiler passes that process tagged services.
 *
 * This base class implements the logic for collecting tagged services, processing their aliases,
 * and registering them with a registry service. It handles service definition preparation and alias mapping.
 * Subclasses should implement the process method to define which services and tags to process.
 */
abstract class AbstractPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param string $tag
     */
    public function processTypes(ContainerBuilder $container, $serviceId, $tag)
    {
        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $types = [];
        $items = $container->findTaggedServiceIds($tag);

        foreach ($items as $id => $attributes) {
            $this->prepareDefinition($container->getDefinition($id));

            foreach ($attributes as $eachTag) {
                $aliases = empty($eachTag['alias']) ? [$id] : explode('|', $eachTag['alias']);

                foreach ($aliases as $alias) {
                    $types[$alias] = $id;
                }
            }
        }

        $extensionDef = $container->getDefinition($serviceId);
        $extensionDef->replaceArgument(1, $types);
    }

    protected function prepareDefinition(Definition $definition)
    {
        $definition->setShared(false)->setPublic(false);
    }
}

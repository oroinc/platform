<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

trait TaggedServicesCompilerPassTrait
{
    /**
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param string $tagName
     * @param string $addMethodName
     */
    public function registerTaggedServices(
        ContainerBuilder $container,
        $serviceId,
        $tagName,
        $addMethodName
    ) {
        if (!$container->hasDefinition($serviceId) ||
                null == ($taggedServiceIds = $container->findTaggedServiceIds($tagName))) {
            return;
        }

        $taggedServices = [];
        foreach ($taggedServiceIds as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $alias = isset($attributes[0]['alias']) ? $attributes[0]['alias'] : $id;
            $taggedServices[$priority] = [new Reference($id), $alias];
        }

        // sort by priority ascending
        ksort($taggedServices);

        // register
        $service = $container->getDefinition($serviceId);
        foreach ($taggedServices as $taggedService) {
            $service->addMethodCall($addMethodName, $taggedService);
        }
    }
}

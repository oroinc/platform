<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Trait that allows to find and sort service by priority option in the tag
 * and register these services by calling the specified method of a "chain" service.
 *
 * IMPORTANT: this trait sorts found tagged services by "ksort" function,
 * as a result, the higher the priority number, the later the service are registered in a chain.
 * It is different from what Symfony proposes, so please be careful using this trait for new tags.
 * Read Symfony's "Reference Tagged Services" article and take a look at PriorityTaggedServiceTrait trait.
 * @link https://symfony.com/doc/current/service_container/tags.html#reference-tagged-services
 * @see \Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait
 */
trait TaggedServicesCompilerPassTrait
{
    /**
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param string $tagName
     * @param string $addMethodName
     */
    private function registerTaggedServices(
        ContainerBuilder $container,
        $serviceId,
        $tagName,
        $addMethodName
    ) {
        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $taggedServiceIds = $container->findTaggedServiceIds($tagName);
        if (count($taggedServiceIds) === 0) {
            return;
        }

        $taggedServices = [];
        foreach ($taggedServiceIds as $id => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            $alias = $attributes[0]['alias'] ?? $id;
            $taggedServices[$priority][] = [new Reference($id), $alias];
        }

        // sort by priority ascending and flatten
        ksort($taggedServices);
        $taggedServices = array_merge(...$taggedServices);

        // register
        $service = $container->getDefinition($serviceId);
        foreach ($taggedServices as $taggedService) {
            $service->addMethodCall($addMethodName, $taggedService);
        }
    }

    /**
     * @param string           $tagName
     * @param ContainerBuilder $container
     *
     * @return Reference[]
     */
    private function findAndSortTaggedServices($tagName, ContainerBuilder $container)
    {
        $services = [];

        $taggedServiceIds = $container->findTaggedServiceIds($tagName, true);
        foreach ($taggedServiceIds as $id => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            $services[$priority][] = new Reference($id);
        }

        if ($services) {
            // sort by priority ascending and flatten
            ksort($services);
            $services = array_merge(...$services);
        }

        return $services;
    }
}

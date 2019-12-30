<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The trait that allows a generic method to find and sort service by priority option in the tag
 * and then build and array of found services keyed by keys received by name option in the tag.
 */
trait PriorityTaggedLocatorTrait
{
    use TaggedServiceTrait;

    /**
     * @param string           $tagName
     * @param string           $nameAttribute
     * @param ContainerBuilder $container
     * @param bool             $inversePriority if TRUE, ksort() will be used instead of krsort() to sort be priority
     *
     * @return Reference[] [service name => service reference, ...]
     */
    private function findAndSortTaggedServices(
        string $tagName,
        string $nameAttribute,
        ContainerBuilder $container,
        bool $inversePriority = false
    ): array {
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $this->getRequiredAttribute($attributes, $nameAttribute, $id, $tagName),
                    $id
                ];
            }
        }

        $services = [];
        if ($items) {
            $items = $this->sortByPriorityAndFlatten($items, $inversePriority);
            foreach ($items as [$key, $id]) {
                if (!isset($services[$key])) {
                    $services[$key] = new Reference($id);
                }
            }
        }

        return $services;
    }
}

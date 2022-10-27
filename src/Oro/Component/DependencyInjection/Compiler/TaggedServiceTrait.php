<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Provides a set of methods to simplify processing tagged services.
 */
trait TaggedServiceTrait
{
    /**
     * Gets a value of the specific tag attribute.
     */
    private function getAttribute(array $attributes, string $attributeName, mixed $defaultValue = null): mixed
    {
        return \array_key_exists($attributeName, $attributes)
            ? $attributes[$attributeName]
            : $defaultValue;
    }

    /**
     * Gets a value of the specific mandatory tag attribute.
     *
     * @throws InvalidArgumentException is the requested attribute does not exist
     */
    private function getRequiredAttribute(
        array $attributes,
        string $attributeName,
        string $serviceId,
        string $tagName
    ): mixed {
        if (!\array_key_exists($attributeName, $attributes)) {
            throw new InvalidArgumentException(sprintf(
                'The attribute "%s" is required for "%s" tag. Service: "%s".',
                $attributeName,
                $tagName,
                $serviceId
            ));
        }

        return $attributes[$attributeName];
    }

    /**
     * Gets a value of the "priority" attribute.
     * If a tag does not have this attribute, 0 is returned.
     */
    private function getPriorityAttribute(array $attributes): int
    {
        return $this->getAttribute($attributes, 'priority', 0);
    }

    /**
     * Sorts tagged services by the priority
     * (the higher the priority number, the earlier the service is added to the result list)
     * and returns flatten array of sorted services.
     *
     * @param array $services [priority => item, ...]
     *
     * @return array [item, ...]
     */
    private function sortByPriorityAndFlatten(array $services): array
    {
        if ($services) {
            krsort($services);
            $services = array_merge(...array_values($services));
        }

        return $services;
    }
}

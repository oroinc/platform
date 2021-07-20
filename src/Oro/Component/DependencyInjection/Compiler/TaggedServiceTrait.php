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
     *
     * @param array  $attributes
     * @param string $attributeName
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    private function getAttribute(array $attributes, string $attributeName, $defaultValue = null)
    {
        return array_key_exists($attributeName, $attributes)
            ? $attributes[$attributeName]
            : $defaultValue;
    }

    /**
     * Gets a value of the specific mandatory tag attribute.
     *
     * @param array  $attributes
     * @param string $attributeName
     * @param string $serviceId
     * @param string $tagName
     *
     * @return mixed
     *
     * @throws InvalidArgumentException is the requested attribute does not exist
     */
    private function getRequiredAttribute(
        array $attributes,
        string $attributeName,
        string $serviceId,
        string $tagName
    ) {
        if (!array_key_exists($attributeName, $attributes)) {
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

    /**
     * Sorts tagged services by the priority using ksort() function
     * (the higher the priority number, the later the service is added to the result list)
     * and returns flatten array of sorted services.
     *
     * @param array $services [priority => item, ...]
     *
     * @return array [item, ...]
     *
     * @deprecated use {@see sortByPriorityAndFlatten} for new tags
     */
    private function inverseSortByPriorityAndFlatten(array $services): array
    {
        if ($services) {
            ksort($services);
            $services = array_merge(...array_values($services));
        }

        return $services;
    }
}

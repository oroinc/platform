<?php

namespace Oro\Component\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Represents a container for config resources.
 */
interface ResourcesContainerInterface
{
    /**
     * Returns an array of resources loaded to build a specific config.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources(): array;

    /**
     * Adds a resource to the container.
     */
    public function addResource(ResourceInterface $resource): void;
}

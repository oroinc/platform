<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

/**
 * An interface for providers of layout theme resources.
 */
interface ResourceProviderInterface
{
    /**
     * Gets all resources.
     */
    public function getResources(): array;

    /**
     * Filters applicable resources by paths.
     *
     * @param string[] $paths
     *
     * @return array
     */
    public function findApplicableResources(array $paths): array;
}

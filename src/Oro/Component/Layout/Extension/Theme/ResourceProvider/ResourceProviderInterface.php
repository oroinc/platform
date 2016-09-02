<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

interface ResourceProviderInterface
{
    /**
     * Filters applicable resources by paths
     *
     * @param array $paths
     *
     * @return array
     */
    public function findApplicableResources(array $paths);
}

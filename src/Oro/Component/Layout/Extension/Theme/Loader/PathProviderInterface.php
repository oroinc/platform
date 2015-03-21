<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

interface PathProviderInterface
{
    const DELIMITER = '/';

    /**
     * Provides paths where applicable resources are located.
     *
     * @param string[] $existingPaths Array of already found paths
     *
     * @return string[] Array of paths imploded with delimiter
     */
    public function getPaths(array $existingPaths);
}

<?php

namespace Oro\Component\Layout\Extension\Theme\PathProvider;

interface PathProviderInterface
{
    public const DELIMITER = DIRECTORY_SEPARATOR;

    /**
     * Provides paths where applicable resources are located.
     *
     * @param string[] $existingPaths Array of already found paths
     *
     * @return string[] Array of paths imploded with delimiter
     */
    public function getPaths(array $existingPaths);
}

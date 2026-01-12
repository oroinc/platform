<?php

namespace Oro\Component\Layout\Extension\Theme\PathProvider;

/**
 * Defines the contract for providing resource paths for theme layouts.
 *
 * Implementations of this interface supply paths where layout resources (templates, configurations, etc.)
 * can be found, building upon existing paths to create a complete search path for theme resources.
 */
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

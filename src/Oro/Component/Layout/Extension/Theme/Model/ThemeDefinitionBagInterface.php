<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Represents a storage for definitions of layout themes.
 */
interface ThemeDefinitionBagInterface
{
    /**
     * Gets names of all known themes.
     *
     * @return string[]
     */
    public function getThemeNames(): array;

    /**
     * Gets a theme definition.
     */
    public function getThemeDefinition(string $themeName): ?array;

    /**
     * Loads resources of theme.yml configuration files
     *
     * @return CumulativeResourceInfo[]
     */
    public function loadThemeResources(ResourcesContainerInterface $resourcesContainer): iterable;
}

<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

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
}

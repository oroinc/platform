<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

/**
 * Defines the contract for creating {@see Theme} instances from theme definitions.
 */
interface ThemeFactoryInterface
{
    /**
     * @param string $themeName
     * @param array  $themeDefinition
     *
     * @return Theme
     */
    public function create($themeName, array $themeDefinition);
}

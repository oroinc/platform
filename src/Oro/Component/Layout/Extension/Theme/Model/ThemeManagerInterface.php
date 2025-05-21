<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

/**
 * Represents a manager for layout themes.
 */
interface ThemeManagerInterface
{
    public function getThemeNames(): array;

    public function getEnabledThemes(null|string|array $groups = null): array;

    public function hasTheme(string $themeName): bool;

    public function getTheme(string $themeName): Theme;

    public function getAllThemes(null|string|array $groups = null): array;

    public function getThemesHierarchy(string $themeName): array;

    public function themeHasParent(string $theme, array $parentThemes): bool;

    public function getThemeConfigOption(string $themeName, string $configOptionName, bool $inherited = true): mixed;

    public function getThemeOption(string $themeName, string $optionName, bool $inherited = true): mixed;
}

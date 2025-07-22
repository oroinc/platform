<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Extension\Theme\Model;

/**
 * Resolves current theme is the old theme or inherited from the old theme
 */
class OldThemeProvider
{
    public function __construct(
        private CurrentThemeProvider $currentThemeProvider,
        private ThemeManager $themeManager
    ) {
    }

    public function isOldTheme(array $parentThemes): bool
    {
        return $this->themeManager->themeHasParent(
            $this->currentThemeProvider->getCurrentThemeId() ?? '',
            $parentThemes
        );
    }
}

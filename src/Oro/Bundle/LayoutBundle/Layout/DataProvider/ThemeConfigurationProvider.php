<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider as GeneralThemeConfigurationProvider;

/**
 * Layout data provider for Theme Configuration options.
 */
class ThemeConfigurationProvider
{
    public function __construct(
        private GeneralThemeConfigurationProvider $generalThemeConfigurationProvider
    ) {
    }

    /**
     * Returns a specific theme configuration option
     */
    public function getThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): mixed {
        return $this->generalThemeConfigurationProvider
            ->getThemeConfigurationOption($configurationKey, $scopeIdentifier);
    }
}

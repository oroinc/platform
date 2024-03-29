<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Provides:
 *  - Theme Configuration from system configuration;
 *  - option value of the Theme Configuration's configuration from system configuration;
 *  - theme name of the Theme Configuration from system configuration;
 */
class ThemeConfigurationProvider
{
    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $registry
    ) {
    }

    public function getThemeConfiguration(object|int|null $scopeIdentifier = null): ?ThemeConfiguration
    {
        $themeConfigurationId = $this->configManager
            ->get('oro_theme.theme_configuration', false, false, $scopeIdentifier);
        if ($themeConfigurationId) {
            return $this->registry
                ->getRepository(ThemeConfiguration::class)
                ->find((int) $themeConfigurationId);
        }

        return null;
    }

    public function hasThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): bool {
        $themeConfiguration = $this->getThemeConfiguration($scopeIdentifier);
        $configuration = $themeConfiguration?->getConfiguration() ?? [];

        return \array_key_exists($configurationKey, $configuration);
    }

    /**
     * Returns a specific theme configuration option
     */
    public function getThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): mixed {
        $themeConfiguration = $this->getThemeConfiguration($scopeIdentifier);
        $configuration = $themeConfiguration?->getConfiguration() ?? [];

        return $configuration[$configurationKey] ?? null;
    }

    public function getThemeName(object|int|null $scopeIdentifier = null): ?string
    {
        $themeConfigurationId = $this->configManager
            ->get('oro_theme.theme_configuration', false, false, $scopeIdentifier);

        return $this->registry
            ->getRepository(ThemeConfiguration::class)
            ->getThemeByThemeConfigurationId($themeConfigurationId);
    }
}

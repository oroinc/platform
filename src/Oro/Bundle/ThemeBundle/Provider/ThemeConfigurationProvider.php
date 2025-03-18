<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides theme configuration options and theme name for a theme that is set in the system configuration.
 */
class ThemeConfigurationProvider implements ResetInterface
{
    private array $configurationCache = [];
    private array $nameCache = [];
    private array $optionsNamesCache = [];

    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $doctrine,
        private ThemeDefinitionBagInterface $configurationProvider
    ) {
    }

    #[\Override]
    public function reset(): void
    {
        $this->configurationCache = [];
        $this->nameCache = [];
        $this->optionsNamesCache = [];
    }

    public function getThemeConfigurationOptions(object|int|null $scopeIdentifier = null): array
    {
        $themeConfigId = $this->getThemeConfigId($scopeIdentifier);
        if (!$themeConfigId) {
            return [];
        }

        if (!\array_key_exists($themeConfigId, $this->configurationCache)) {
            $this->configurationCache[$themeConfigId] = $this->loadThemeConfigurationValues(
                $scopeIdentifier,
                $themeConfigId
            );
        }

        return $this->configurationCache[$themeConfigId];
    }

    public function hasThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): bool {
        return \array_key_exists($configurationKey, $this->getThemeConfigurationOptions($scopeIdentifier));
    }

    public function getThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): mixed {
        return $this->getThemeConfigurationOptions($scopeIdentifier)[$configurationKey] ?? null;
    }

    public function getThemeName(object|int|null $scopeIdentifier = null): ?string
    {
        $themeConfigId = $this->getThemeConfigId($scopeIdentifier);
        if (!$themeConfigId) {
            return null;
        }

        if (!\array_key_exists($themeConfigId, $this->nameCache)) {
            $this->nameCache[$themeConfigId] = $this->loadValue($themeConfigId, 'theme');
        }

        return $this->nameCache[$themeConfigId];
    }

    public function getThemeConfigurationOptionsNamesByType(
        string $type,
        object|int|null $scopeIdentifier = null
    ): array {
        $themeName = $this->getThemeName($scopeIdentifier);
        if (!$themeName) {
            return [];
        }

        if (!\array_key_exists($type, $this->optionsNamesCache)) {
            $this->optionsNamesCache[$type] = $this->loadOptionsNamesByType($type, $themeName);
        }

        return $this->optionsNamesCache[$type];
    }

    private function getThemeConfigId(object|int|null $scopeIdentifier = null): ?int
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
            false,
            false,
            $scopeIdentifier
        );
    }

    private function loadOptionsNamesByType(string $type, string $themeName): array
    {
        $optionsNames = [];
        $config = $this->configurationProvider->getThemeDefinition($themeName);
        foreach ($config['configuration']['sections'] ?? [] as $sKey => $section) {
            foreach ($section['options'] as $oKey => $option) {
                if ($type === $option['type']) {
                    $optionsNames[] = LayoutThemeConfiguration::buildOptionKey($sKey, $oKey);
                }
            }
        }

        return $optionsNames;
    }

    private function loadThemeConfigurationValues(object|int|null $scopeIdentifier = null, int $themeConfigId): array
    {
        $savedConfig = $this->loadValue($themeConfigId, 'configuration') ?? [];
        $themeName = $this->nameCache[$themeConfigId] ?? $this->getThemeName($scopeIdentifier);

        if ($themeName) {
            $this->loadDefaultDefinedOptions($themeName, $savedConfig);
        }

        return $savedConfig;
    }

    private function loadDefaultDefinedOptions(string $themeName, array &$savedConfig): void
    {
        $config = $this->configurationProvider->getThemeDefinition($themeName);
        foreach ($config['configuration']['sections'] ?? [] as $sKey => $section) {
            foreach ($section['options'] as $oKey => $option) {
                $optionKey = LayoutThemeConfiguration::buildOptionKey($sKey, $oKey);

                if (isset($option['default']) && !\array_key_exists($optionKey, $savedConfig)) {
                    $value = $option['default'];
                    if ($option['type'] === 'checkbox') {
                        $value = $option['default'] === 'checked';
                    }

                    $savedConfig[$optionKey] = $value;
                }
            }
        }
    }

    private function loadValue(int $themeConfigId, string $fieldName): mixed
    {
        return $this->doctrine
            ->getRepository(ThemeConfiguration::class)
            ->getFieldValue($themeConfigId, $fieldName);
    }
}

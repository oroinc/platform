<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettings;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;

/**
 * The registry that allows to get the API configuration extensions.
 */
class ConfigExtensionRegistry
{
    private int $maxNestingLevel;
    /** @var ConfigExtensionInterface[] */
    private array $extensions = [];
    private ?ConfigurationSettingsInterface $configurationSettings = null;
    /** @var string[]|null */
    private ?array $configSectionNames = null;

    /**
     * @param int $maxNestingLevel The maximum number of nesting target entities
     */
    public function __construct(int $maxNestingLevel = 0)
    {
        $this->maxNestingLevel = $maxNestingLevel;
    }

    /**
     * Returns the maximum number of nesting target entities.
     */
    public function getMaxNestingLevel(): int
    {
        return $this->maxNestingLevel;
    }

    /**
     * Registers the configuration extension.
     */
    public function addExtension(ConfigExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
        $this->configurationSettings = null;
        $this->configSectionNames = null;
    }

    /**
     * Returns all registered configuration extensions.
     *
     * @return ConfigExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Collects the configuration definition settings from all registered extensions.
     */
    public function getConfigurationSettings(): ConfigurationSettingsInterface
    {
        if (null === $this->configurationSettings) {
            $settings = new ConfigurationSettings();
            $extensions = $this->getExtensions();
            foreach ($extensions as $extension) {
                $sections = $extension->getEntityConfigurationSections();
                foreach ($sections as $sectionName => $sectionConfiguration) {
                    $sectionConfiguration->setSettings($settings);
                    $settings->addExtraSection($sectionName, $sectionConfiguration);
                }
                $callbacks = $extension->getConfigureCallbacks();
                foreach ($callbacks as $section => $callback) {
                    $settings->addConfigureCallback($section, $callback);
                }
                $callbacks = $extension->getPreProcessCallbacks();
                foreach ($callbacks as $section => $callback) {
                    $settings->addPreProcessCallback($section, $callback);
                }
                $callbacks = $extension->getPostProcessCallbacks();
                foreach ($callbacks as $section => $callback) {
                    $settings->addPostProcessCallback($section, $callback);
                }
            }
            $this->configurationSettings = $settings;
        }

        return $this->configurationSettings;
    }

    /**
     * Returns names of all registered configuration sections.
     *
     * @return string[]
     */
    public function getConfigSectionNames(): array
    {
        if (null === $this->configSectionNames) {
            $sectionNameMap = [];
            $extensions = $this->getExtensions();
            foreach ($extensions as $extension) {
                $sections = $extension->getEntityConfigurationSections();
                foreach ($sections as $name => $configuration) {
                    if (!isset($sectionNameMap[$name])) {
                        $sectionNameMap[$name] = true;
                    }
                }
            }
            $this->configSectionNames = array_keys($sectionNameMap);
        }

        return $this->configSectionNames;
    }
}

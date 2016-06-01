<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettings;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;

class ConfigExtensionRegistry
{
    /** @var int */
    protected $maxNestingLevel = [];

    /** @var ConfigExtensionInterface[] */
    protected $extensions = [];

    /**
     * @param int $maxNestingLevel The maximum number of nesting target entities
     */
    public function __construct($maxNestingLevel = 0)
    {
        if (!is_int($maxNestingLevel)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The $maxNestingLevel must be an integer. Got: %s.',
                    gettype($maxNestingLevel)
                )
            );
        }

        $this->maxNestingLevel = $maxNestingLevel;
    }

    /**
     * Returns the maximum number of nesting target entities.
     *
     * @return int
     */
    public function getMaxNestingLevel()
    {
        return $this->maxNestingLevel;
    }

    /**
     * Registers the configuration extension.
     *
     * @param ConfigExtensionInterface $extension
     */
    public function addExtension(ConfigExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * Returns all registered configuration extensions.
     *
     * @return ConfigExtensionInterface[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Collects the configuration definition settings from all registered extensions.
     *
     * @return ConfigurationSettingsInterface
     */
    public function getConfigurationSettings()
    {
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

        return $settings;
    }
}

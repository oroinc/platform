<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

/**
 * The implementation of the settings for nested builders for "entities" and "relations" configuration sections.
 */
class DefinitionConfigurationSettings implements ConfigurationSettingsInterface
{
    /** @var ConfigurationSettingsInterface */
    private $settings;

    /** @var array [section path => callback[], ...] */
    private $additionalConfigureCallbacks = [];

    /**
     * @param ConfigurationSettingsInterface $settings
     */
    public function __construct(ConfigurationSettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraSections(): array
    {
        return $this->settings->getExtraSections();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks(string $section): array
    {
        $callbacks = $this->settings->getConfigureCallbacks($section);
        if (isset($this->additionalConfigureCallbacks[$section])) {
            $callbacks = array_merge($callbacks, $this->additionalConfigureCallbacks[$section]);
        }

        return $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreProcessCallbacks(string $section): array
    {
        return $this->settings->getPreProcessCallbacks($section);
    }

    /**
     * {@inheritdoc}
     */
    public function getPostProcessCallbacks(string $section): array
    {
        return $this->settings->getPostProcessCallbacks($section);
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addAdditionalConfigureCallback(string $section, $callback): void
    {
        $this->additionalConfigureCallbacks[$section][] = $callback;
    }
}

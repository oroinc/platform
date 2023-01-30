<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

/**
 * The implementation of the settings for nested builder for "entities" configuration section.
 */
class DefinitionConfigurationSettings implements ConfigurationSettingsInterface
{
    private ConfigurationSettingsInterface $settings;
    /** @var array [section path => callback[], ...] */
    private array $additionalConfigureCallbacks = [];

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

    public function addAdditionalConfigureCallback(string $section, callable $callback): void
    {
        $this->additionalConfigureCallbacks[$section][] = $callback;
    }
}

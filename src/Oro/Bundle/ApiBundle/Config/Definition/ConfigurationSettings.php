<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

/**
 * The base implementation of the settings for configuration section builders.
 */
class ConfigurationSettings implements ConfigurationSettingsInterface
{
    /** @var ConfigurationSectionInterface[] [section name => ConfigurationSectionInterface, ...] */
    private $extraSections = [];

    /** @var array [section path => callback[], ...] */
    private $configureCallbacks = [];

    /** @var array [section path => callback[], ...] */
    private $preProcessCallbacks = [];

    /** @var array [section path => callback[], ...] */
    private $postProcessCallbacks = [];

    /**
     * {@inheritdoc}
     */
    public function getExtraSections(): array
    {
        return $this->extraSections;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks(string $section): array
    {
        return $this->configureCallbacks[$section] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPreProcessCallbacks(string $section): array
    {
        return $this->preProcessCallbacks[$section] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPostProcessCallbacks(string $section): array
    {
        return $this->postProcessCallbacks[$section] ?? [];
    }

    /**
     * @param string                        $sectionName
     * @param ConfigurationSectionInterface $section
     */
    public function addExtraSection(string $sectionName, ConfigurationSectionInterface $section): void
    {
        $this->extraSections[$sectionName] = $section;
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addConfigureCallback(string $section, $callback): void
    {
        $this->configureCallbacks[$section][] = $callback;
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addPreProcessCallback(string $section, $callback): void
    {
        $this->preProcessCallbacks[$section][] = $callback;
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addPostProcessCallback(string $section, $callback): void
    {
        $this->postProcessCallbacks[$section][] = $callback;
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

/**
 * The base implementation of the settings for configuration section builders.
 */
class ConfigurationSettings implements ConfigurationSettingsInterface
{
    /** @var ConfigurationSectionInterface[] [section name => ConfigurationSectionInterface, ...] */
    private array $extraSections = [];
    /** @var array [section path => callback[], ...] */
    private array $configureCallbacks = [];
    /** @var array [section path => callback[], ...] */
    private array $preProcessCallbacks = [];
    /** @var array [section path => callback[], ...] */
    private array $postProcessCallbacks = [];

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

    public function addExtraSection(string $sectionName, ConfigurationSectionInterface $section): void
    {
        $this->extraSections[$sectionName] = $section;
    }

    public function addConfigureCallback(string $section, callable $callback): void
    {
        $this->configureCallbacks[$section][] = $callback;
    }

    public function addPreProcessCallback(string $section, callable $callback): void
    {
        $this->preProcessCallbacks[$section][] = $callback;
    }

    public function addPostProcessCallback(string $section, callable $callback): void
    {
        $this->postProcessCallbacks[$section][] = $callback;
    }
}

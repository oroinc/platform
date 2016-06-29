<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

class ConfigurationSettings implements ConfigurationSettingsInterface
{
    /** @var array [section name => ConfigurationSectionInterface[], ...] */
    protected $extraSections = [];

    /** @var array [section path => callback[], ...] */
    protected $configureCallbacks = [];

    /** @var array [section path => callback[], ...] */
    protected $preProcessCallbacks = [];

    /** @var array [section path => callback[], ...] */
    protected $postProcessCallbacks = [];

    /**
     * {@inheritdoc}
     */
    public function getExtraSections()
    {
        return $this->extraSections;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks($section)
    {
        return isset($this->configureCallbacks[$section])
            ? $this->configureCallbacks[$section]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPreProcessCallbacks($section)
    {
        return isset($this->preProcessCallbacks[$section])
            ? $this->preProcessCallbacks[$section]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPostProcessCallbacks($section)
    {
        return isset($this->postProcessCallbacks[$section])
            ? $this->postProcessCallbacks[$section]
            : [];
    }

    /**
     * @param string                        $sectionName
     * @param ConfigurationSectionInterface $section
     */
    public function addExtraSection($sectionName, ConfigurationSectionInterface $section)
    {
        $this->extraSections[$sectionName] = $section;
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addConfigureCallback($section, $callback)
    {
        $this->configureCallbacks[$section][] = $callback;
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addPreProcessCallback($section, $callback)
    {
        $this->preProcessCallbacks[$section][] = $callback;
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addPostProcessCallback($section, $callback)
    {
        $this->postProcessCallbacks[$section][] = $callback;
    }
}

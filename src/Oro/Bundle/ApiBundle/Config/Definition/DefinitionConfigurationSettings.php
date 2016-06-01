<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

class DefinitionConfigurationSettings implements ConfigurationSettingsInterface
{
    /** @var ConfigurationSettingsInterface */
    protected $settings;

    /** @var array [section path => callback[], ...] */
    protected $additionalConfigureCallbacks = [];

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
    public function getExtraSections()
    {
        return $this->settings->getExtraSections();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks($section)
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
    public function getPreProcessCallbacks($section)
    {
        return $this->settings->getPreProcessCallbacks($section);
    }

    /**
     * {@inheritdoc}
     */
    public function getPostProcessCallbacks($section)
    {
        return $this->settings->getPostProcessCallbacks($section);
    }

    /**
     * @param string   $section
     * @param callable $callback
     */
    public function addAdditionalConfigureCallback($section, $callback)
    {
        $this->additionalConfigureCallbacks[$section][] = $callback;
    }
}

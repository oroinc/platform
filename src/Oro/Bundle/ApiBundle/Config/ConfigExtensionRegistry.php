<?php

namespace Oro\Bundle\ApiBundle\Config;

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
     * @return array [extraSections, configureCallbacks, preProcessCallbacks, postProcessCallbacks]
     */
    public function getConfigurationSettings()
    {
        $extraSections        = [];
        $configureCallbacks   = [];
        $preProcessCallbacks  = [];
        $postProcessCallbacks = [];

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $sections = $extension->getEntityConfigurationSections();
            foreach ($sections as $sectionName => $sectionConfiguration) {
                $extraSections[$sectionName] = $sectionConfiguration;
            }
            $callbacks = $extension->getConfigureCallbacks();
            foreach ($callbacks as $section => $callback) {
                $configureCallbacks[$section][] = $callback;
            }
            $callbacks = $extension->getPreProcessCallbacks();
            foreach ($callbacks as $section => $callback) {
                $preProcessCallbacks[$section][] = $callback;
            }
            $callbacks = $extension->getPostProcessCallbacks();
            foreach ($callbacks as $section => $callback) {
                $postProcessCallbacks[$section][] = $callback;
            }
        }

        return [
            $extraSections,
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
        ];
    }
}

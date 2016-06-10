<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

abstract class AbstractConfigurationSection implements ConfigurationSectionInterface
{
    /** @var ConfigurationSettingsInterface */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function isApplicable($section)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings(ConfigurationSettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param NodeBuilder $node
     * @param string      $section
     *
     * @return array
     */
    protected function callConfigureCallbacks(NodeBuilder $node, $section)
    {
        $callbacks = $this->settings->getConfigureCallbacks($section);
        foreach ($callbacks as $callback) {
            call_user_func($callback, $node);
        }
    }

    /**
     * @param ArrayNodeDefinition $node
     * @param string              $sectionName
     * @param callable|null       $customPreProcessCallback
     */
    protected function addPreProcessCallbacks(
        ArrayNodeDefinition $node,
        $sectionName,
        $customPreProcessCallback = null
    ) {
        $node
            ->beforeNormalization()
            ->always(
                function ($value) use ($sectionName, $customPreProcessCallback) {
                    if (null !== $customPreProcessCallback) {
                        $value = call_user_func($customPreProcessCallback, $value);
                    }
                    $callbacks = $this->settings->getPreProcessCallbacks($sectionName);
                    foreach ($callbacks as $callback) {
                        $value = call_user_func($callback, $value);
                    }

                    return $value;
                }
            );
    }

    /**
     * @param ArrayNodeDefinition $node
     * @param string              $sectionName
     * @param callable|null       $customPostProcessCallback
     */
    protected function addPostProcessCallbacks(
        ArrayNodeDefinition $node,
        $sectionName,
        $customPostProcessCallback = null
    ) {
        $node
            ->validate()
            ->always(
                function ($value) use ($sectionName, $customPostProcessCallback) {
                    if (null !== $customPostProcessCallback) {
                        $value = call_user_func($customPostProcessCallback, $value);
                    }
                    $callbacks = $this->settings->getPostProcessCallbacks($sectionName);
                    foreach ($callbacks as $callback) {
                        $value = call_user_func($callback, $value);
                    }

                    return $value;
                }
            );
    }
}

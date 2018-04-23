<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The base class for different kind of configuration section builders.
 */
abstract class AbstractConfigurationSection implements ConfigurationSectionInterface
{
    /** @var ConfigurationSettingsInterface */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function isApplicable(string $section): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings(ConfigurationSettingsInterface $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @param NodeBuilder $node
     * @param string      $section
     */
    protected function callConfigureCallbacks(NodeBuilder $node, string $section): void
    {
        $callbacks = $this->settings->getConfigureCallbacks($section);
        foreach ($callbacks as $callback) {
            \call_user_func($callback, $node);
        }
    }

    /**
     * @param ArrayNodeDefinition $node
     * @param string              $section
     * @param callable|null       $customPreProcessCallback
     */
    protected function addPreProcessCallbacks(
        ArrayNodeDefinition $node,
        string $section,
        $customPreProcessCallback = null
    ): void {
        $node
            ->beforeNormalization()
            ->always(
                function ($value) use ($section, $customPreProcessCallback) {
                    if (null !== $customPreProcessCallback) {
                        $value = \call_user_func($customPreProcessCallback, $value);
                    }
                    $callbacks = $this->settings->getPreProcessCallbacks($section);
                    foreach ($callbacks as $callback) {
                        $value = \call_user_func($callback, $value);
                    }

                    return $value;
                }
            );
    }

    /**
     * @param ArrayNodeDefinition $node
     * @param string              $section
     * @param callable|null       $customPostProcessCallback
     */
    protected function addPostProcessCallbacks(
        ArrayNodeDefinition $node,
        string $section,
        $customPostProcessCallback = null
    ): void {
        $node
            ->validate()
            ->always(
                function ($value) use ($section, $customPostProcessCallback) {
                    if (null !== $customPostProcessCallback) {
                        $value = \call_user_func($customPostProcessCallback, $value);
                    }
                    $callbacks = $this->settings->getPostProcessCallbacks($section);
                    foreach ($callbacks as $callback) {
                        $value = \call_user_func($callback, $value);
                    }

                    return $value;
                }
            );
    }
}

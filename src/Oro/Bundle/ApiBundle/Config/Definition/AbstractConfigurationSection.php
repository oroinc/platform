<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The base class for different kind of configuration section builders.
 */
abstract class AbstractConfigurationSection implements ConfigurationSectionInterface
{
    protected ConfigurationSettingsInterface $settings;

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

    protected function callConfigureCallbacks(NodeBuilder $node, string $section): void
    {
        $callbacks = $this->settings->getConfigureCallbacks($section);
        foreach ($callbacks as $callback) {
            $callback($node);
        }
    }

    protected function addPreProcessCallbacks(
        ArrayNodeDefinition $node,
        string $section,
        callable $customPreProcessCallback = null
    ): void {
        $node
            ->beforeNormalization()
            ->always(
                function ($value) use ($section, $customPreProcessCallback) {
                    if (null !== $customPreProcessCallback) {
                        $value = $customPreProcessCallback($value);
                    }
                    $callbacks = $this->settings->getPreProcessCallbacks($section);
                    foreach ($callbacks as $callback) {
                        $value = $callback($value);
                    }

                    return $value;
                }
            );
    }

    protected function addPostProcessCallbacks(
        ArrayNodeDefinition $node,
        string $section,
        callable $customPostProcessCallback = null
    ): void {
        $node
            ->validate()
            ->always(
                function ($value) use ($section, $customPostProcessCallback) {
                    if (null !== $customPostProcessCallback) {
                        $value = $customPostProcessCallback($value);
                    }
                    $callbacks = $this->settings->getPostProcessCallbacks($section);
                    foreach ($callbacks as $callback) {
                        $value = $callback($value);
                    }

                    return $value;
                }
            );
    }
}

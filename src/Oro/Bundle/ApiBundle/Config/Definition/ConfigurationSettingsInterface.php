<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

/**
 * Represents the settings for configuration section builders.
 */
interface ConfigurationSettingsInterface
{
    /**
     * @return ConfigurationSectionInterface[] [section name => ConfigurationSectionInterface, ...]
     */
    public function getExtraSections(): array;

    /**
     * @param string $section
     *
     * @return callable[]
     */
    public function getConfigureCallbacks(string $section): array;

    /**
     * @param string $section
     *
     * @return callable[]
     */
    public function getPreProcessCallbacks(string $section): array;

    /**
     * @param string $section
     *
     * @return callable[]
     */
    public function getPostProcessCallbacks(string $section): array;
}

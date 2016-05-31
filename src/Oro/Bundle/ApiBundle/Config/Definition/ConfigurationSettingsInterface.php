<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

interface ConfigurationSettingsInterface
{
    /**
     * @return ConfigurationSectionInterface[] [section name => ConfigurationSectionInterface, ...]
     */
    public function getExtraSections();

    /**
     * @param string $section
     *
     * @return callable[]
     */
    public function getConfigureCallbacks($section);

    /**
     * @param string $section
     *
     * @return callable[]
     */
    public function getPreProcessCallbacks($section);

    /**
     * @param string $section
     *
     * @return callable[]
     */
    public function getPostProcessCallbacks($section);
}

<?php

namespace Oro\Component\ConfigExpression\ConfigurationPass;

/**
 * Defines the contract for configuration passes that process and transform expression configuration.
 *
 * Configuration passes are used to modify or validate expression configuration data before
 * it is assembled into expression objects. They enable preprocessing of configuration arrays
 * to support features like property path replacement and other transformations.
 */
interface ConfigurationPassInterface
{
    /**
     * Pass through configuration data, processes it and returns modified data
     *
     * @param array $configuration
     * @return array
     */
    public function passConfiguration(array $configuration);
}

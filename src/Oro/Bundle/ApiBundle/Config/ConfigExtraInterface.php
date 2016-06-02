<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Provides an interface for different kind requests for configuration data.
 */
interface ConfigExtraInterface
{
    /**
     * Returns a string which is used as unique identifier of configuration data.
     *
     * @return string
     */
    public function getName();

    /**
     * Makes modifications of the ConfigContext necessary to get required configuration data.
     *
     * @param ConfigContext $context
     */
    public function configureContext(ConfigContext $context);

    /**
     * Indicates whether this config extra should be used when a configuration of related entities will be built.
     *
     * @return bool
     */
    public function isPropagable();

    /**
     * Returns a string that should be added to a cache key used by the config providers.
     *
     * @return string|null
     */
    public function getCacheKeyPart();
}

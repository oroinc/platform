<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Provides an interface for different kind requests for additional configuration data.
 */
interface ConfigExtraInterface
{
    /**
     * Gets a string that uniquely identifies a type of additional data.
     *
     * @return string
     */
    public function getName();

    /**
     * Makes modifications of the ConfigContext necessary to get required additional data.
     *
     * @param ConfigContext $context
     */
    public function configureContext(ConfigContext $context);

    /**
     * Indicates whether this config extra is applicable to nested configs.
     *
     * @return bool
     */
    public function isPropagable();

    /**
     * Returns a string that should be used as a part of a cache key used by config providers.
     *
     * @return string|null
     */
    public function getCacheKeyPart();
}

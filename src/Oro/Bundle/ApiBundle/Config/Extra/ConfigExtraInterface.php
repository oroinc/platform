<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * Provides an interface for different kind requests for configuration data.
 */
interface ConfigExtraInterface
{
    /**
     * Returns a string which is used as unique identifier of configuration data.
     */
    public function getName(): string;

    /**
     * Makes modifications of the ConfigContext necessary to get required configuration data.
     */
    public function configureContext(ConfigContext $context): void;

    /**
     * Indicates whether this config extra should be used when a configuration of related entities is built.
     */
    public function isPropagable(): bool;

    /**
     * Returns a string that should be added to a cache key used by the config providers.
     */
    public function getCacheKeyPart(): ?string;
}

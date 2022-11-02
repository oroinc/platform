<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Provides an interface for classes that can be used to get the configuration of API resource
 * outside of API processors.
 */
interface ConfigAccessorInterface
{
    /**
     * Gets the configuration of an entity.
     */
    public function getConfig(string $className): ?EntityDefinitionConfig;
}

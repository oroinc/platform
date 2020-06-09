<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * This interface should be implemented by filters that depend on an entity configuration.
 */
interface ConfigAwareFilterInterface
{
    /**
     * Sets the entity configuration.
     *
     * @param EntityDefinitionConfig $config
     */
    public function setConfig(EntityDefinitionConfig $config): void;
}

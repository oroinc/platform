<?php

namespace Oro\Bundle\ApiBundle\Config;

interface ConfigAccessorInterface
{
    /**
     * Gets configuration of an entity.
     *
     * @param string $className
     *
     * @return EntityDefinitionConfig|null
     */
    public function getConfig($className);
}

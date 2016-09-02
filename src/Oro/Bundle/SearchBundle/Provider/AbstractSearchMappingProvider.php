<?php

namespace Oro\Bundle\SearchBundle\Provider;

abstract class AbstractSearchMappingProvider
{
    /**
     * @return array
     */
    abstract public function getMappingConfig();

    /**
     * @param string $entityClass
     * @return string
     */
    abstract public function getEntityAlias($entityClass);
}

<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;

interface EntityAliasProviderInterface
{
    /**
     * Returns the alias and plural alias for the given entity class.
     * If the provider cannot return aliases it should return NULL.
     *
     * @param string $entityClass
     *
     * @return EntityAlias|null|bool The instance of EntityAlias if this provider knows about the given entity
     *                               NULL if this provider doesn't know which alias should be used for the given entity
     *                               FALSE if the given entity should be excluded
     */
    public function getEntityAlias($entityClass);
}

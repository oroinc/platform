<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasConfigBag
{
    /** @var array */
    protected $entityAliases;

    /** @var array */
    protected $exclusions;

    /**
     * @param array $entityAliases
     * @param array $exclusions
     */
    public function __construct(array $entityAliases, array $exclusions)
    {
        $this->entityAliases = $entityAliases;
        $this->exclusions    = array_fill_keys($exclusions, true);
    }

    /**
     * Determines whether a configuration of entity aliases for the given entity exists.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function hasEntityAlias($entityClass)
    {
        return isset($this->entityAliases[$entityClass]);
    }

    /**
     * Returns entity aliases for the given entity.
     *
     * @param string $entityClass
     *
     * @return EntityAlias
     */
    public function getEntityAlias($entityClass)
    {
        return new EntityAlias(
            $this->entityAliases[$entityClass]['alias'],
            $this->entityAliases[$entityClass]['plural_alias']
        );
    }

    /**
     * Determines whether the given entity should be excluded from entity aliases.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function isEntityAliasExclusionExist($entityClass)
    {
        return isset($this->exclusions[$entityClass]);
    }

    /**
     * Returns class names for all entities that have an alias configuration.
     *
     * @return string[]
     */
    public function getClassNames()
    {
        return array_keys($this->entityAliases);
    }
}

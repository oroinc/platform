<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Model\EntityAlias;

/**
 * A storage for configuration of entity aliases defined in "Resources/config/oro/entity.yml" files.
 */
class EntityAliasConfigBag
{
    /** @var EntityConfigurationProvider */
    private $configProvider;

    /** @var array */
    private $exclusions;

    public function __construct(EntityConfigurationProvider $configProvider)
    {
        $this->configProvider = $configProvider;
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
        $entityAliases = $this->getEntityAliases();

        return isset($entityAliases[$entityClass]);
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
        $entityAliases = $this->getEntityAliases();

        return new EntityAlias(
            $entityAliases[$entityClass]['alias'],
            $entityAliases[$entityClass]['plural_alias']
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
        if (null === $this->exclusions) {
            $this->exclusions = array_fill_keys(
                $this->configProvider->getConfiguration(EntityConfiguration::ENTITY_ALIAS_EXCLUSIONS),
                true
            );
        }

        return isset($this->exclusions[$entityClass]);
    }

    /**
     * Returns class names for all entities that have an alias configuration.
     *
     * @return string[]
     */
    public function getClassNames()
    {
        return array_keys($this->getEntityAliases());
    }

    /**
     * @return array
     */
    private function getEntityAliases()
    {
        return $this->configProvider->getConfiguration(EntityConfiguration::ENTITY_ALIASES);
    }
}

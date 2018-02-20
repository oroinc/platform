<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;

/**
 * The entity aliases provider that returns Data API specific aliases
 * configured via "Resources/config/oro/api.yml" files.
 */
class EntityAliasProvider implements EntityAliasProviderInterface, EntityClassProviderInterface
{
    /** @var array */
    private $entityAliases;

    /** @var array */
    private $exclusions;

    /**
     * @param array $entityAliases The Data API specific aliases
     * @param array $exclusions    The Data API specific exclusions
     */
    public function __construct(array $entityAliases, array $exclusions)
    {
        $this->entityAliases = $entityAliases;
        $this->exclusions = array_fill_keys($exclusions, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        if (isset($this->exclusions[$entityClass])) {
            return false;
        }
        if (!isset($this->entityAliases[$entityClass])) {
            return null;
        }

        return new EntityAlias(
            $this->entityAliases[$entityClass]['alias'],
            $this->entityAliases[$entityClass]['plural_alias']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames()
    {
        return array_keys($this->entityAliases);
    }
}

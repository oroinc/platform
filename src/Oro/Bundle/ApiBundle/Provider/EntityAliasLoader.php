<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader as BaseEntityAliasLoader;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;

/**
 * Loads entity aliases from the registered providers to EntityAliasStorage
 * taking into account overridden entities.
 */
class EntityAliasLoader extends BaseEntityAliasLoader
{
    private EntityOverrideProviderInterface $entityOverrideProvider;

    /**
     * @param iterable<EntityClassProviderInterface> $entityClassProviders
     * @param iterable<EntityAliasProviderInterface> $entityAliasProviders
     * @param EntityOverrideProviderInterface        $entityOverrideProvider
     */
    public function __construct(
        iterable $entityClassProviders,
        iterable $entityAliasProviders,
        EntityOverrideProviderInterface $entityOverrideProvider
    ) {
        parent::__construct($entityClassProviders, $entityAliasProviders);
        $this->entityOverrideProvider = $entityOverrideProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityAlias(string $entityClass): ?EntityAlias
    {
        // do not add aliases for entities that were overridden
        if (null !== $this->entityOverrideProvider->getSubstituteEntityClass($entityClass)) {
            return null;
        }

        return parent::getEntityAlias($entityClass);
    }
}

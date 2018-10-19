<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader as BaseEntityAliasLoader;

/**
 * Loads entity aliases from the registered providers to EntityAliasStorage
 * taking into account overridden entities.
 */
class EntityAliasLoader extends BaseEntityAliasLoader
{
    /** @var EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /**
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     */
    public function __construct(EntityOverrideProviderInterface $entityOverrideProvider)
    {
        $this->entityOverrideProvider = $entityOverrideProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityAlias($entityClass)
    {
        // do not add aliases for entities that were overridden
        if (null !== $this->entityOverrideProvider->getSubstituteEntityClass($entityClass)) {
            return null;
        }

        return parent::getEntityAlias($entityClass);
    }
}

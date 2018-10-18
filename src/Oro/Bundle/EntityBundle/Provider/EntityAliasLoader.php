<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Exception\DuplicateEntityAliasException;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;

/**
 * Loads entity aliases from the registered providers to EntityAliasStorage.
 */
class EntityAliasLoader
{
    /** @var EntityClassProviderInterface[] */
    private $entityClassProviders = [];

    /** @var EntityAliasProviderInterface[] */
    private $entityAliasProviders = [];

    /**
     * Registers entity class name provider.
     *
     * @param EntityClassProviderInterface $provider
     */
    public function addEntityClassProvider(EntityClassProviderInterface $provider)
    {
        $this->entityClassProviders[] = $provider;
    }

    /**
     * Registers entity alias provider.
     *
     * @param EntityAliasProviderInterface $provider
     */
    public function addEntityAliasProvider(EntityAliasProviderInterface $provider)
    {
        $this->entityAliasProviders[] = $provider;
    }

    /**
     * Loads entity aliases into the storage.
     *
     * @param EntityAliasStorage $storage
     *
     * @throws InvalidEntityAliasException if an alias or a plural alias for any entity is not valid
     * @throws DuplicateEntityAliasException if duplicated entity alias is detected
     */
    public function load(EntityAliasStorage $storage)
    {
        foreach ($this->entityClassProviders as $entityClassProvider) {
            $entityClasses = $entityClassProvider->getClassNames();
            foreach ($entityClasses as $entityClass) {
                if (null === $storage->getEntityAlias($entityClass)) {
                    $entityAlias = $this->getEntityAlias($entityClass);
                    if (null !== $entityAlias) {
                        $storage->addEntityAlias($entityClass, $entityAlias);
                    }
                }
            }
        }
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias|null
     */
    protected function getEntityAlias($entityClass)
    {
        $entityAlias = null;
        foreach ($this->entityAliasProviders as $entityAliasProvider) {
            $entityAlias = $entityAliasProvider->getEntityAlias($entityClass);
            if (null !== $entityAlias) {
                break;
            }
        }
        if (false === $entityAlias) {
            $entityAlias = null;
        }

        return $entityAlias;
    }
}

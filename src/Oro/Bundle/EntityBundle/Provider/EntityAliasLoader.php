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
    /** @var iterable|EntityClassProviderInterface[] */
    private $entityClassProviders;

    /** @var iterable|EntityAliasProviderInterface[] */
    private $entityAliasProviders;

    /**
     * @param iterable|EntityClassProviderInterface[] $entityClassProviders
     * @param iterable|EntityAliasProviderInterface[] $entityAliasProviders
     */
    public function __construct(iterable $entityClassProviders, iterable $entityAliasProviders)
    {
        $this->entityClassProviders = $entityClassProviders;
        $this->entityAliasProviders = $entityAliasProviders;
    }

    /**
     * Loads entity aliases into the storage.
     *
     * @throws InvalidEntityAliasException if an alias or a plural alias for any entity is not valid
     * @throws DuplicateEntityAliasException if duplicated entity alias is detected
     */
    public function load(EntityAliasStorage $storage): void
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

    protected function getEntityAlias(string $entityClass): ?EntityAlias
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

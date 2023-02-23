<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver as BaseEntityAliasResolver;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides functionality to get singular and plural aliases for an entity class
 * and resolve entity class by any of these aliases taking into account overridden entities.
 */
class EntityAliasResolver extends BaseEntityAliasResolver
{
    private EntityOverrideProviderInterface $entityOverrideProvider;
    /** @var string[] */
    private array $configFiles;

    /**
     * @param EntityAliasLoader               $loader
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     * @param CacheItemPoolInterface          $cache
     * @param LoggerInterface                 $logger
     * @param string[]                        $configFiles
     */
    public function __construct(
        EntityAliasLoader $loader,
        EntityOverrideProviderInterface $entityOverrideProvider,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger,
        array $configFiles
    ) {
        parent::__construct($loader, $cache, $logger);
        $this->entityOverrideProvider = $entityOverrideProvider;
        $this->configFiles = $configFiles;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAlias($entityClass)
    {
        return parent::hasAlias($this->resolveEntityClass($entityClass));
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias($entityClass)
    {
        return parent::getAlias($this->resolveEntityClass($entityClass));
    }

    /**
     * {@inheritDoc}
     */
    public function getPluralAlias($entityClass)
    {
        return parent::getPluralAlias($this->resolveEntityClass($entityClass));
    }

    /**
     * {@inheritDoc}
     */
    protected function createStorage()
    {
        return new EntityAliasStorage($this->configFiles);
    }

    private function resolveEntityClass(string $entityClass): string
    {
        $substituteEntityClass = $this->entityOverrideProvider->getSubstituteEntityClass($entityClass);
        if ($substituteEntityClass) {
            return $substituteEntityClass;
        }

        return $entityClass;
    }
}

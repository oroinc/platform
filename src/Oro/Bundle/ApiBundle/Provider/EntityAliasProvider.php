<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;

/**
 * The entity aliases provider that returns API specific aliases
 * configured via "Resources/config/oro/api.yml" files.
 */
class EntityAliasProvider implements EntityAliasProviderInterface, EntityClassProviderInterface
{
    private ConfigCache $configCache;
    private ?array $entityAliases = null;
    private array $exclusions;

    public function __construct(ConfigCache $configCache)
    {
        $this->configCache = $configCache;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityAlias($entityClass)
    {
        $this->ensureInitialized();

        if (isset($this->exclusions[$entityClass])) {
            return false;
        }

        if (!isset($this->entityAliases[$entityClass])) {
            return null;
        }

        $aliases = $this->entityAliases[$entityClass];
        if (empty($aliases)) {
            return null;
        }

        return new EntityAlias($aliases['alias'], $aliases['plural_alias']);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassNames(): array
    {
        $this->ensureInitialized();

        return array_keys($this->entityAliases);
    }

    private function ensureInitialized(): void
    {
        if (null === $this->entityAliases) {
            $this->entityAliases = $this->configCache->getAliases();
            $this->exclusions = array_fill_keys($this->configCache->getExcludedEntities(), true);
        }
    }
}

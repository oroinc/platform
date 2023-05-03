<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * The entity override provider that returns substitutions
 * configured via "override_class" option in "Resources/config/oro/api.yml" files.
 */
class EntityOverrideProvider implements EntityOverrideProviderInterface
{
    private ConfigCache $configCache;
    /** @var string[]|null [class name => substitute class name, ...] */
    private ?array $substitutions = null;

    public function __construct(ConfigCache $configCache)
    {
        $this->configCache = $configCache;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubstituteEntityClass(string $entityClass): ?string
    {
        if (null === $this->substitutions) {
            $this->substitutions = $this->configCache->getSubstitutions();
        }

        return $this->substitutions[$entityClass] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass(string $substituteClass): ?string
    {
        if (null === $this->substitutions) {
            $this->substitutions = $this->configCache->getSubstitutions();
        }

        $entityClass = array_search($substituteClass, $this->substitutions, true);
        if (false === $entityClass) {
            return null;
        }

        return $entityClass;
    }
}

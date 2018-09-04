<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * The entity override provider that returns substitutions
 * configured via "override_class" option in "Resources/config/oro/api.yml" files.
 */
class EntityOverrideProvider implements EntityOverrideProviderInterface
{
    /** @var ConfigCache */
    private $configCache;

    /** @var string[] [class name => substitute class name, ...] */
    private $substitutions;

    /**
     * @param ConfigCache $configCache
     */
    public function __construct(ConfigCache $configCache)
    {
        $this->configCache = $configCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubstituteEntityClass(string $entityClass): ?string
    {
        if (null === $this->substitutions) {
            $this->substitutions = $this->configCache->getSubstitutions();
        }

        if (!isset($this->substitutions[$entityClass])) {
            return null;
        }

        return $this->substitutions[$entityClass];
    }
}

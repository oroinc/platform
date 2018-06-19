<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * The entity override provider that returns substitutions
 * configured via "override_class" option in "Resources/config/oro/api.yml" files.
 */
class EntityOverrideProvider implements EntityOverrideProviderInterface
{
    /** @var string[] [class name => substitute class name, ...] */
    private $substitutions;

    /**
     * @param string[] $substitutions [class name => substitute class name, ...]
     */
    public function __construct(array $substitutions)
    {
        $this->substitutions = $substitutions;
    }

    /**
     * @param string $entityClass
     * @param string $substituteEntityClass
     */
    public function addSubstitution(string $entityClass, string $substituteEntityClass): void
    {
        $this->substitutions[$entityClass] = $substituteEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubstituteEntityClass(string $entityClass): ?string
    {
        if (!isset($this->substitutions[$entityClass])) {
            return null;
        }

        return $this->substitutions[$entityClass];
    }
}

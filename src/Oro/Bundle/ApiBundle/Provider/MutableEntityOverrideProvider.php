<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * The entity override provider that returns substitutions
 * and allows to add new substitutions in runtime via addSubstitution method.
 */
class MutableEntityOverrideProvider implements EntityOverrideProviderInterface
{
    /** @var string[] [class name => substitute class name, ...] */
    private array $substitutions;

    /**
     * @param string[] $substitutions [class name => substitute class name, ...]
     */
    public function __construct(array $substitutions = [])
    {
        $this->substitutions = $substitutions;
    }

    public function addSubstitution(string $entityClass, string $substituteEntityClass): void
    {
        $this->substitutions[$entityClass] = $substituteEntityClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubstituteEntityClass(string $entityClass): ?string
    {
        return $this->substitutions[$entityClass] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass(string $substituteClass): ?string
    {
        $entityClass = array_search($substituteClass, $this->substitutions, true);
        if (false === $entityClass) {
            return null;
        }

        return $entityClass;
    }
}

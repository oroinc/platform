<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Delegates the returning of entity substitution to child providers.
 */
class ChainEntityOverrideProvider implements EntityOverrideProviderInterface
{
    /** @var EntityOverrideProviderInterface[] */
    private array $providers;

    /**
     * @param EntityOverrideProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubstituteEntityClass(string $entityClass): ?string
    {
        foreach ($this->providers as $provider) {
            $substituteEntityClass = $provider->getSubstituteEntityClass($entityClass);
            if ($substituteEntityClass) {
                return $substituteEntityClass;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass(string $substituteClass): ?string
    {
        foreach ($this->providers as $provider) {
            $entityClass = $provider->getEntityClass($substituteClass);
            if ($entityClass) {
                return $entityClass;
            }
        }

        return null;
    }
}

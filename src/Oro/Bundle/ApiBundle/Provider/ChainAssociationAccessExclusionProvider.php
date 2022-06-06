<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Delegates a work to child association access exclusion providers.
 */
class ChainAssociationAccessExclusionProvider implements AssociationAccessExclusionProviderInterface
{
    /** @var AssociationAccessExclusionProviderInterface[] */
    private array $providers;

    /**
     * @param AssociationAccessExclusionProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoreAssociationAccessCheck(string $entityClass, string $associationName): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isIgnoreAssociationAccessCheck($entityClass, $associationName)) {
                return true;
            }
        }

        return false;
    }
}

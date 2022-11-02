<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Delegates the getting of entity class names to child providers.
 */
class ChainEntityClassNameProvider implements EntityClassNameProviderInterface
{
    /** @var iterable|EntityClassNameProviderInterface[] */
    private $providers;

    /**
     * @param iterable|EntityClassNameProviderInterface[] $providers
     */
    public function __construct(iterable $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(string $entityClass): ?string
    {
        foreach ($this->providers as $provider) {
            $name = $provider->getEntityClassName($entityClass);
            if ($name) {
                return $name;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassPluralName(string $entityClass): ?string
    {
        foreach ($this->providers as $provider) {
            $name = $provider->getEntityClassPluralName($entityClass);
            if ($name) {
                return $name;
            }
        }

        return null;
    }
}

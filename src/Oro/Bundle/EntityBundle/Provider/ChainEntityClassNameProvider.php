<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ChainEntityClassNameProvider implements EntityClassNameProviderInterface
{
    /** @var EntityClassNameProviderInterface[] */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param EntityClassNameProviderInterface $provider
     */
    public function addProvider(EntityClassNameProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName($entityClass)
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
    public function getEntityClassPluralName($entityClass)
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

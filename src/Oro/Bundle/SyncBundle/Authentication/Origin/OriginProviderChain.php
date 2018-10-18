<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

/**
 * Store all origin providers to have one point to get all origins
 */
class OriginProviderChain implements OriginProviderInterface
{
    /**
     * @var OriginProviderInterface[]
     */
    private $providers = [];

    /**
     * @param OriginProviderInterface $provider
     */
    public function addProvider(OriginProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrigins(): array
    {
        $origins = [];

        foreach ($this->providers as $provider) {
            $origins = array_merge($origins, $provider->getOrigins());
        }

        return array_unique($origins);
    }
}

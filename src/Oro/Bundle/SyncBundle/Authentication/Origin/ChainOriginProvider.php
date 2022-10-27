<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

/**
 * Collects origins from all child providers.
 */
class ChainOriginProvider implements OriginProviderInterface
{
    /** @var iterable|OriginProviderInterface[] */
    private $providers;

    /**
     * @param iterable|OriginProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrigins(): array
    {
        $origins = [];
        foreach ($this->providers as $provider) {
            $origins[] = $provider->getOrigins();
        }
        if ($origins) {
            $origins = array_values(array_unique(array_merge(...$origins)));
        }

        return $origins;
    }
}

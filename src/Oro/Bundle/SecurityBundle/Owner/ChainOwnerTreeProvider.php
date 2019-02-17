<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedOwnerTreeProviderException;

/**
 * Delegates returning of owner tree to suitable child provider.
 */
class ChainOwnerTreeProvider implements OwnerTreeProviderInterface
{
    /** @var OwnerTreeProviderInterface[] */
    private $providers = [];

    /** @var OwnerTreeProviderInterface */
    private $defaultProvider;

    /**
     * @param OwnerTreeProviderInterface $provider
     */
    public function addProvider(OwnerTreeProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @param OwnerTreeProviderInterface $defaultProvider
     */
    public function setDefaultProvider(OwnerTreeProviderInterface $defaultProvider): void
    {
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(): bool
    {
        if ($this->defaultProvider) {
            return true;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return OwnerTreeInterface
     */
    public function getTree(): OwnerTreeInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return $provider->getTree();
            }
        }

        if ($this->defaultProvider) {
            return $this->defaultProvider->getTree();
        }

        throw new UnsupportedOwnerTreeProviderException('Supported provider not found in chain');
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        foreach ($this->providers as $provider) {
            $provider->clearCache();
        }

        if ($this->defaultProvider) {
            $this->defaultProvider->clearCache();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        foreach ($this->providers as $provider) {
            $provider->warmUpCache();
        }

        if ($this->defaultProvider) {
            $this->defaultProvider->warmUpCache();
        }
    }
}

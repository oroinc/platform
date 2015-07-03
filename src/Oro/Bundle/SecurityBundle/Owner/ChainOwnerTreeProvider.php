<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedOwnerTreeProviderException;

class ChainOwnerTreeProvider implements OwnerTreeProviderInterface
{
    /**
     * @var ArrayCollection|OwnerTreeProviderInterface[]
     */
    protected $providers;

    /**
     * @var OwnerTreeProviderInterface
     */
    protected $defaultProvider;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    /**
     * @param OwnerTreeProviderInterface $provider
     */
    public function addProvider(OwnerTreeProviderInterface $provider)
    {
        if ($this->providers->contains($provider)) {
            return;
        }

        $this->providers->add($provider);
    }

    /**
     * @param OwnerTreeProviderInterface $defaultProvider
     */
    public function setDefaultProvider($defaultProvider)
    {
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
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
     * @return OwnerTree
     */
    public function getTree()
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
    public function clear()
    {
        foreach ($this->providers as $provider) {
            $provider->clear();
        }

        if ($this->defaultProvider) {
            $this->defaultProvider->clear();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache()
    {
        foreach ($this->providers as $provider) {
            $provider->warmUpCache();
        }

        if ($this->defaultProvider) {
            $this->defaultProvider->warmUpCache();
        }
    }
}

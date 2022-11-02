<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedOwnerTreeProviderException;

/**
 * Delegates returning of owner tree to suitable child provider.
 */
class ChainOwnerTreeProvider implements OwnerTreeProviderInterface
{
    /** @var iterable|OwnerTreeProviderInterface[] */
    private $providers;

    /**
     * @param iterable|OwnerTreeProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return true;
            }
        }

        return false;
    }

    public function getTree(): OwnerTreeInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return $provider->getTree();
            }
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
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        foreach ($this->providers as $provider) {
            $provider->warmUpCache();
        }
    }
}

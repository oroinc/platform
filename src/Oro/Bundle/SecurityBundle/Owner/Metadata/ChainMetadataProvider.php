<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Exception\NoSupportsMetadataProviderException;

class ChainMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var MetadataProviderInterface[]
     */
    protected $providers;

    /**
     * @param MetadataProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Adds all providers that marked by tag: oro_security.owner.metadata_provider
     *
     * @param MetadataProviderInterface $provider
     */
    public function addProvider(MetadataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($className)
    {
        return $this->getSupportedProvider()->getMetadata($className);
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicLevelClass()
    {
        return $this->getSupportedProvider()->getBasicLevelClass();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalLevelClass($deep = false)
    {
        return $this->getSupportedProvider()->getLocalLevelClass($deep);
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalLevelClass()
    {
        return $this->getSupportedProvider()->getGlobalLevelClass();
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemLevelClass()
    {
        return $this->getSupportedProvider()->getSystemLevelClass();
    }

    /**
     * @return MetadataProviderInterface
     *
     * @throws NoSupportsMetadataProviderException
     */
    protected function getSupportedProvider()
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return $provider;
            }
        }

        throw new NoSupportsMetadataProviderException();
    }
}

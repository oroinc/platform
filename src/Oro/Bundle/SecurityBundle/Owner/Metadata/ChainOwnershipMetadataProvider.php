<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException;

/**
 * Chain of ownership metadata providers
 */
class ChainOwnershipMetadataProvider implements OwnershipMetadataProviderInterface
{
    /** @var OwnershipMetadataProviderInterface[] */
    protected $providers = [];

    /** @var OwnershipMetadataProviderInterface */
    protected $supportedProvider;

    /** @var OwnershipMetadataProviderInterface */
    protected $defaultProvider;

    /** @var OwnershipMetadataProviderInterface */
    protected $emulatedProvider;

    /**
     * Adds all providers that marked by tag: oro_security.owner.metadata_provider
     *
     * @param string $alias
     * @param OwnershipMetadataProviderInterface $provider
     */
    public function addProvider($alias, OwnershipMetadataProviderInterface $provider)
    {
        $this->providers[$alias] = $provider;
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
     * {@inheritDoc}
     */
    public function getMetadata($className)
    {
        return $this->getSupportedProvider()->getMetadata($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserClass()
    {
        return $this->getSupportedProvider()->getUserClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessUnitClass()
    {
        return $this->getSupportedProvider()->getBusinessUnitClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationClass()
    {
        return $this->getSupportedProvider()->getOrganizationClass();
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxAccessLevel($accessLevel, $className = null)
    {
        return $this->getSupportedProvider()->getMaxAccessLevel($accessLevel, $className);
    }

    /**
     * @param string $providerAlias
     */
    public function startProviderEmulation($providerAlias)
    {
        if (!isset($this->providers[$providerAlias])) {
            throw new \InvalidArgumentException(sprintf('Provider with "%s" alias not registered', $providerAlias));
        }

        $this->emulatedProvider = $this->providers[$providerAlias];
    }

    public function stopProviderEmulation()
    {
        $this->emulatedProvider = null;
    }

    /**
     * @return OwnershipMetadataProviderInterface
     */
    protected function getSupportedProvider()
    {
        if ($this->emulatedProvider) {
            return $this->emulatedProvider;
        }

        if ($this->supportedProvider) {
            return $this->supportedProvider;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                $this->supportedProvider = $provider;

                return $this->supportedProvider;
            }
        }

        if ($this->defaultProvider) {
            return $this->defaultProvider;
        }

        throw new UnsupportedMetadataProviderException('Supported provider not found in chain');
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache($className = null)
    {
        foreach ($this->providers as $provider) {
            $provider->clearCache($className);
        }

        if ($this->defaultProvider) {
            $this->defaultProvider->clearCache($className);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache($className = null)
    {
        foreach ($this->providers as $provider) {
            $provider->warmUpCache($className);
        }

        if ($this->defaultProvider) {
            $this->defaultProvider->warmUpCache($className);
        }
    }

    /**
     * @param OwnershipMetadataProviderInterface $defaultProvider
     */
    public function setDefaultProvider($defaultProvider)
    {
        $this->defaultProvider = $defaultProvider;
    }
}

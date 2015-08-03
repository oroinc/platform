<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException;

class ChainMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var ArrayCollection|MetadataProviderInterface[]
     */
    protected $providers;

    /**
     * @var MetadataProviderInterface
     */
    protected $supportedProvider;

    /**
     * @var MetadataProviderInterface
     */
    protected $defaultProvider;

    /**
     * @var MetadataProviderInterface
     */
    protected $emulatedProvider;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    /**
     * Adds all providers that marked by tag: oro_security.owner.metadata_provider
     *
     * @param string $alias
     * @param MetadataProviderInterface $provider
     */
    public function addProvider($alias, MetadataProviderInterface $provider)
    {
        $this->providers->set($alias, $provider);
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
        if (!$this->providers->containsKey($providerAlias)) {
            throw new \InvalidArgumentException(sprintf('Provider with "%s" alias not registered', $providerAlias));
        }

        $this->emulatedProvider = $this->providers->get($providerAlias);
    }

    public function stopProviderEmulation()
    {
        $this->emulatedProvider = null;
    }

    /**
     * @return MetadataProviderInterface
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
     * @param MetadataProviderInterface $defaultProvider
     */
    public function setDefaultProvider($defaultProvider)
    {
        $this->defaultProvider = $defaultProvider;
    }
}

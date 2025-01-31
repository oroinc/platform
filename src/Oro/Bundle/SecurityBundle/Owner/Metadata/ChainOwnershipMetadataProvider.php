<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException;

/**
 * Represents a chain of ownership metadata providers.
 */
class ChainOwnershipMetadataProvider implements OwnershipMetadataProviderInterface
{
    private ?OwnershipMetadataProviderInterface $defaultProvider = null;
    /** @var OwnershipMetadataProviderInterface[] */
    private array $providers = [];
    private ?OwnershipMetadataProviderInterface $supportedProvider = null;
    private ?OwnershipMetadataProviderInterface $emulatedProvider = null;

    public function setDefaultProvider(OwnershipMetadataProviderInterface $defaultProvider): void
    {
        $this->defaultProvider = $defaultProvider;
    }

    public function addProvider(string $alias, OwnershipMetadataProviderInterface $provider): void
    {
        $this->providers[$alias] = $provider;
    }

    public function startProviderEmulation(string $providerAlias): void
    {
        if (!isset($this->providers[$providerAlias])) {
            throw new \InvalidArgumentException(sprintf('Provider with "%s" alias not registered', $providerAlias));
        }

        $this->emulatedProvider = $this->providers[$providerAlias];
    }

    public function stopProviderEmulation(): void
    {
        $this->emulatedProvider = null;
    }

    #[\Override]
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

    #[\Override]
    public function getMetadata(?string $className): OwnershipMetadataInterface
    {
        return $this->getSupportedProvider()->getMetadata($className);
    }

    #[\Override]
    public function getUserClass(): string
    {
        return $this->getSupportedProvider()->getUserClass();
    }

    #[\Override]
    public function getBusinessUnitClass(): string
    {
        return $this->getSupportedProvider()->getBusinessUnitClass();
    }

    #[\Override]
    public function getOrganizationClass(): ?string
    {
        return $this->getSupportedProvider()->getOrganizationClass();
    }

    #[\Override]
    public function getMaxAccessLevel(int $accessLevel, ?string $className = null): int
    {
        return $this->getSupportedProvider()->getMaxAccessLevel($accessLevel, $className);
    }

    #[\Override]
    public function clearCache(?string $className = null): void
    {
        foreach ($this->providers as $provider) {
            $provider->clearCache($className);
        }

        $this->defaultProvider?->clearCache($className);
    }

    #[\Override]
    public function warmUpCache(?string $className = null): void
    {
        foreach ($this->providers as $provider) {
            $provider->warmUpCache($className);
        }

        $this->defaultProvider?->warmUpCache($className);
    }

    private function getSupportedProvider(): OwnershipMetadataProviderInterface
    {
        if (null !== $this->emulatedProvider) {
            return $this->emulatedProvider;
        }

        if (null !== $this->supportedProvider) {
            return $this->supportedProvider;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                $this->supportedProvider = $provider;

                return $this->supportedProvider;
            }
        }

        if (null !== $this->defaultProvider) {
            return $this->defaultProvider;
        }

        throw new UnsupportedMetadataProviderException('Supported provider not found in chain');
    }
}

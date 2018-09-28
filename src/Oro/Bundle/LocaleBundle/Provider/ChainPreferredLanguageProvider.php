<?php

namespace Oro\Bundle\LocaleBundle\Provider;

/**
 * Chain provider for preferred language providers allows to extend it's behavior by adding preferred language providers
 * from other bundles.
 * This class should be injected as a dependency in services where entity's preferred language is needed.
 */
class ChainPreferredLanguageProvider implements PreferredLanguageProviderInterface
{
    /**
     * @var array|PreferredLanguageProviderInterface[]
     */
    private $providers = [];

    /**
     * @param PreferredLanguageProviderInterface $provider
     */
    public function addProvider(PreferredLanguageProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entity): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getPreferredLanguage($entity): string
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($entity)) {
                return $provider->getPreferredLanguage($entity);
            }
        }

        throw new \LogicException(
            sprintf('No preferred language provider for the "%s" entity class exists', \get_class($entity))
        );
    }
}

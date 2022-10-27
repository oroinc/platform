<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Chain provider for preferred localization providers allows to extend it's behavior by adding preferred localization
 * providers from other bundles.
 * This class should be injected as a dependency in services where entity's preferred language is needed.
 */
class ChainPreferredLocalizationProvider implements PreferredLocalizationProviderInterface
{
    /**
     * @var iterable|PreferredLocalizationProviderInterface[]
     */
    private $providers;

    /**
     * @param PreferredLocalizationProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getPreferredLocalization($entity): ?Localization
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($entity)) {
                $localization = $provider->getPreferredLocalization($entity);
                if ($localization) {
                    return $localization;
                }
            }
        }

        // Always exists DefaultPreferredLocalizationProvider, if his return null
        throw new \LogicException(
            sprintf('No preferred localization provider for the "%s" entity class exists', \get_class($entity))
        );
    }
}

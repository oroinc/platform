<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Interface for preferred localization providers which determine for the given entity in which localization
 * the notification should be sent
 */
interface PreferredLocalizationProviderInterface
{
    /**
     * Returns true if entity is supported by provider.
     *
     * @param object|null $entity
     * @return bool
     */
    public function supports($entity): bool;

    /**
     * @param object $entity
     * @return Localization|null
     * @throws \LogicException
     */
    public function getPreferredLocalization($entity): ?Localization;
}

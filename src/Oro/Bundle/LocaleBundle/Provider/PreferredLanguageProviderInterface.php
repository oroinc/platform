<?php

namespace Oro\Bundle\LocaleBundle\Provider;

/**
 * Interface for preferred language providers which determine for the given entity in which language notification
 * should be sent.
 */
interface PreferredLanguageProviderInterface
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
     * @return string
     * @throws \LogicException
     */
    public function getPreferredLanguage($entity): string;
}

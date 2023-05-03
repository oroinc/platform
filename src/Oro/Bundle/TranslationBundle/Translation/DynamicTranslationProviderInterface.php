<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Represents a service to get translations updated by a user.
 */
interface DynamicTranslationProviderInterface
{
    /**
     * Gets a translation of the given message.
     */
    public function getTranslation(string $id, string $domain, string $locale): string;

    /**
     * Checks if the given message has a translation.
     */
    public function hasTranslation(string $id, string $domain, string $locale): bool;

    /**
     * Gets all translations for the given domain and locale.
     *
     * @param string $domain
     * @param string $locale
     *
     * @return array [message id => message, ...]
     */
    public function getTranslations(string $domain, string $locale): array;

    /**
     * Sets the fallback locales.
     *
     * @param string[] $locales
     */
    public function setFallbackLocales(array $locales): void;

    /**
     * Warms up the cache for all translations for the given locale.
     */
    public function warmUp(array $locales): void;
}

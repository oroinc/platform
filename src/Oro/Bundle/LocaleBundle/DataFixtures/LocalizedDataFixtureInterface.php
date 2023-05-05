<?php

namespace Oro\Bundle\LocaleBundle\DataFixtures;

/**
 * Denotes data fixtures that include localizable data.
 */
interface LocalizedDataFixtureInterface extends LocalizationOptionsAwareInterface
{
    /**
     * Should return the list of supported locales, e.g. ['en_US', 'de_DE', 'fr_FR'].
     *
     * @return string[]
     */
    public function getSupportedLocales(): array;

    /**
     * Should return a valid locale based on the current object state and its supported locales.
     */
    public function getLocale(?string $locale = null): string;
}

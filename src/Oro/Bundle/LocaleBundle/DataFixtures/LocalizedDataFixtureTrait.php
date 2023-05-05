<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\DataFixtures;

/**
 * Boilerplate implementation of LocalizedDataFixtureInterface.
 * Exhibiting classes must define
 *      const SUPPORTED_LOCALES = [string,...];
 *  e.g.
 *      public const SUPPORTED_LOCALES = ['en_US', 'de_DE', 'fr_FR'];
 * @see LocalizedDataFixtureInterface
 * @see LocalizationOptionsAwareInterface
 */
trait LocalizedDataFixtureTrait
{
    use LocalizationOptionsAwareTrait;

    /**
     * @return string[]
     */
    public function getSupportedLocales() : array
    {
        return static::SUPPORTED_LOCALES;
    }

    public function getLocale(?string $locale = null): string
    {
        if (null !== $locale) {
            return \in_array($locale, $this->getSupportedLocales()) ? $locale : 'en_US';
        }
        return \in_array($this->formattingCode, $this->getSupportedLocales()) ? $this->formattingCode : 'en_US';
    }

    protected function getLocalizedValue(string $key, array $row, ?string $locale = null): string
    {
        if ($this instanceof LocalizedDataFixtureInterface) {
            if (null === $locale) {
                $locale = $this->getLocale();
            }
            return $row[$key][$locale]
                ?? $row[$key . '.' . $locale]
                ?? $row[$key]['en_US']
                ?? $row[$key . '.en_US']
                ?? $row[$key];
        }
        return $row[$key];
    }
}

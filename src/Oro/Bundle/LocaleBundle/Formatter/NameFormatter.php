<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\LocaleBundle\Model\NameInterface;
use Oro\Bundle\LocaleBundle\Model\NamePrefixInterface;
use Oro\Bundle\LocaleBundle\Model\NameSuffixInterface;

class NameFormatter
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    // @codingStandardsIgnoreStart
    /**
     * @param NamePrefixInterface|FirstNameInterface|MiddleNameInterface|LastNameInterface|NameSuffixInterface|NameInterface $person
     * @param null|string $locale
     * @return string
     */
    // @codingStandardsIgnoreEnd
    public function format($person, $locale = null)
    {
        $nameParts = [];
        if ($person instanceof NamePrefixInterface) {
            $nameParts['prefix'] = $person->getNamePrefix();
        }
        if ($person instanceof FirstNameInterface) {
            $nameParts['first_name'] = $person->getFirstName();
        }
        if ($person instanceof MiddleNameInterface) {
            $nameParts['middle_name'] = $person->getMiddleName();
        }
        if ($person instanceof LastNameInterface) {
            $nameParts['last_name'] = $person->getLastName();
        }
        if ($person instanceof NameSuffixInterface) {
            $nameParts['suffix'] = $person->getNameSuffix();
        }

        if (empty($nameParts) && $person instanceof NameInterface) {
            return $person->getName();
        }

        $format = $this->getNameFormat($locale);

        return $this->applyFormat($nameParts, $format);
    }

    /**
     * Get name format based on locale, if locale is not passed locale from system configuration will be used.
     *
     * @param string|null $locale
     * @throws \RuntimeException
     */
    public function getNameFormat($locale = null)
    {
        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        $nameFormats = $this->localeSettings->getNameFormats();

        // match by locale (for example - "fr_CA")
        if (isset($nameFormats[$locale])) {
            return $nameFormats[$locale];
        }

        // match by locale language (for example - "fr")
        $localeParts = \Locale::parseLocale($locale);
        if (isset($localeParts[\Locale::LANG_TAG])) {
            $match = $localeParts[\Locale::LANG_TAG];
            if (isset($match, $nameFormats[$match])) {
                return $nameFormats[$match];
            }
        }

        // match by default locale in system configuration settings
        $match = $this->localeSettings->getLocale();
        if ($match !== $locale && isset($nameFormats[$match])) {
            return $nameFormats[$match];
        }

        // fallback to default constant locale
        $match = LocaleConfiguration::DEFAULT_LOCALE;
        if (isset($nameFormats[$match])) {
            return $nameFormats[$match];
        }

        throw new \RuntimeException(sprintf('Cannot get name format for "%s"', $locale));
    }

    /**
     * @param array $nameParts
     * @param string $format
     * @return string
     */
    protected function applyFormat(array $nameParts, $format)
    {
        $name = preg_replace_callback(
            '/%(\w+)%/',
            function ($data) use ($nameParts) {
                $key = $data[1];
                $lowerCaseKey = strtolower($key);
                if (isset($nameParts[$lowerCaseKey])) {
                    if ($key !== $lowerCaseKey) {
                        $nameParts[$lowerCaseKey] = strtoupper($nameParts[$lowerCaseKey]);
                    }
                    return $nameParts[$lowerCaseKey];
                }
                return '';
            },
            $format
        );

        return trim(preg_replace('/ +/', ' ', $name));
    }
}

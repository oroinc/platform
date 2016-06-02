<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Normalizer;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleCodeFormatter
{
    const DEFAULT_LOCALE = 'default';

    /**
     * @param Locale|string $locale
     * @return string
     */
    public static function formatName($locale = null)
    {
        if (!$locale) {
            return self::DEFAULT_LOCALE;
        }

        if ($locale instanceof Locale) {
            $code = $locale->getCode();
            if (!$code) {
                return self::DEFAULT_LOCALE;
            }

            return (string)$code;
        }

        return (string)$locale;
    }

    /**
     * @param Locale|string $locale
     * @return string
     */
    public static function formatKey($locale = null)
    {
        if (!$locale) {
            return null;
        }

        if ($locale instanceof Locale) {
            $code = $locale->getCode();
            if (!$code) {
                return null;
            }

            return (string)$code;
        }

        return (string)$locale;
    }
}

<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Normalizer;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationCodeFormatter
{
    const DEFAULT_LOCALIZATION = 'default';

    /**
     * @param Localization|string $localization
     * @return string
     */
    public static function formatName($localization = null)
    {
        if (!$localization) {
            return self::DEFAULT_LOCALIZATION;
        }

        if ($localization instanceof Localization) {
            $code = $localization->getLanguageCode();
            if (!$code) {
                return self::DEFAULT_LOCALIZATION;
            }

            return (string)$code;
        }

        return (string)$localization;
    }

    /**
     * @param Localization|string $localization
     * @return string
     */
    public static function formatKey($localization = null)
    {
        if (!$localization) {
            return null;
        }

        if ($localization instanceof Localization) {
            $code = $localization->getLanguageCode();
            if (!$code) {
                return null;
            }

            return (string)$code;
        }

        return (string)$localization;
    }
}

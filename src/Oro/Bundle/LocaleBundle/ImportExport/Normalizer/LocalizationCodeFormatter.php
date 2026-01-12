<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Normalizer;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Utility class for formatting localization codes and names for import/export operations.
 *
 * This class provides static methods to convert {@see Localization} entities or string
 * identifiers into standardized format codes and keys used during data import and
 * export. It handles null values by returning a default localization identifier,
 * and supports both Localization objects and string representations.
 */
class LocalizationCodeFormatter
{
    public const DEFAULT_LOCALIZATION = 'default';

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
            $name = $localization->getName();
            if (!$name) {
                return self::DEFAULT_LOCALIZATION;
            }

            return (string)$name;
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
            $name = $localization->getName();
            if (!$name) {
                return null;
            }

            return (string)$name;
        }

        return (string)$localization;
    }
}

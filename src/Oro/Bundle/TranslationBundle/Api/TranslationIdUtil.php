<?php

namespace Oro\Bundle\TranslationBundle\Api;

/**
 * Provides a set of static methods to work with ID of translation API resource.
 */
class TranslationIdUtil
{
    private const DELIMITER = '-';

    public static function buildTranslationId(int $translationKeyId, string $languageCode): string
    {
        return $translationKeyId . self::DELIMITER . $languageCode;
    }

    public static function extractTranslationKeyId(string $translationId): ?int
    {
        $delimiterPos = strpos($translationId, self::DELIMITER);
        if (false === $delimiterPos) {
            return null;
        }

        $value = substr($translationId, 0, $delimiterPos);
        if (!$value || !is_numeric($value)) {
            return null;
        }

        $normalizedValue = (int)$value;
        if (((string)$normalizedValue) !== $value) {
            return null;
        }

        return $normalizedValue;
    }

    public static function extractLanguageCode(string $translationId): ?string
    {
        $delimiterPos = strpos($translationId, self::DELIMITER);
        if (false === $delimiterPos) {
            return null;
        }

        $value = substr($translationId, $delimiterPos + 1);
        if (!$value) {
            return null;
        }

        return $value;
    }
}

<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Download;

/**
 * Provides translation metrics (translation completeness and last build date) from the translation service.
 */
interface TranslationMetricsProviderInterface
{
    /**
     * Returns translation metrics from the translation service for all available (translated) languages.
     *
     * The translation metrics are returned as an array:
     * <code>
     * [
     *     'uk_UA' => [
     *         'code' => 'uk_UA',                       // full language code, including locality
     *         'altCode' => 'uk',                       // optional, may not be present
     *         'translationStatus' => 30,               // percentage of translated strings or words (varies by service)
     *         'lastBuildDate' => \DateTimeInterface    // object with the last translation build date
     *     ],
     *     // ...
     * ]
     * </code>
     */
    public function getAll(): array;

    /**
     * Returns translation metrics for the specified language as an array:
     * <code>
     * [
     *     'code' => 'uk_UA',                       // full language code, including locality
     *     'altCode' => 'uk',                       // optional, may not be present
     *     'translationStatus' => 30,               // percentage of translated strings or words (varies by service)
     *     'lastBuildDate' => \DateTimeInterface    // object with the last translation build date
     * ]
     * </code>
     *
     * If the specified language is not available on the translation service, this method will return null.
     */
    public function getForLanguage(string $languageCode): ?array;
}

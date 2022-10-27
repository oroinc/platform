<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Download;

use Oro\Bundle\TranslationBundle\Exception\TranslationServiceAdapterException;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceInvalidResponseException;

/**
 * An interface for 3-rd party translation service adapter implementations.
 */
interface TranslationServiceAdapterInterface
{
    /**
     * Fetches translation metrics (translation completeness status and last build date) for all languages
     * that are supported by the translation service.
     *
     * The metrics are returned as the following array:
     * <code>
     *  [
     *     'uk_UA' => [
     *         'code' => 'uk_UA',           // full language code, including locality
     *         'altCode' => 'uk',           // optional, may not be present
     *         'translationStatus' => 30,   // percentage of translated strings or words (meaning varies by service)
     *         'lastBuildDate' => '2020-08-24T20:08:24+0300' // any format recognized by PHP date functions
     *     ],
     *     // ...
     * ]
     * </code>
     *
     * @throws TranslationServiceAdapterException if a translation service is unavailable
     * @throws TranslationServiceInvalidResponseException if a response of a translation service request
     *                                                    cannot be decoded or return invalid metrics
     */
    public function fetchTranslationMetrics(): array;

    /**
     * Downloads an archive with translations for a language and saves it at the specified path.
     *
     * Please note that the saved file may have an additional extension (suffix) added by the adapter
     * (e.g. with the provided path '/tmp/download-spanish.dict' the translation archive can actually be saved as
     * '/tmp/download-spanish.dict.zip', '/tmp/download-spanish.dict.tar.gz' or similar) unless you already provided
     * the file path with exactly the same suffix that is used by the adapter (which is generally not needed).
     * Simply do not attempt to unpack the archive yourself, but rather use the extractTranslationsFromArchive() method
     * of the same adapter and pass the original file path that was passed to the downloadTranslationsArchive() method.
     *
     * @param string $languageCode full language code, including locality, e.g. "fr_FR", "fr_CA".
     * @param string $pathToSaveDownloadedArchive
     *
     * @throws TranslationServiceAdapterException if failed to download or save the translation archive
     */
    public function downloadLanguageTranslationsArchive(
        string $languageCode,
        string $pathToSaveDownloadedArchive
    ): void;

    /**
     * Extracts translation files from an archive into the specified directory.
     *
     * @param string $pathToArchive use the same value that you passed to downloadLanguageTranslationsArchive() before.
     * @param string $directoryPathToExtractTo
     * @param string $languageCode full language code, including locality, e.g. "fr_FR", "fr_CA".
     * @throws TranslationServiceAdapterException if failed to extract for any reason
     */
    public function extractTranslationsFromArchive(
        string $pathToArchive,
        string $directoryPathToExtractTo,
        string $languageCode
    ): void;
}

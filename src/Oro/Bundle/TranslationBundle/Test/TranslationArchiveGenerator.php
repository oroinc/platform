<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Test;

/**
 * Can be used in tests to create archives containing translations for a language.
 */
class TranslationArchiveGenerator
{
    public const SMALL_TRANSLATION_SET = [
        'messages' => ['key1' => 'tr1', 'key2' => 'tr2'],
        'validation' => ['key3' => 'tr3', 'key4' => 'tr4'],
    ];

    /**
     * Creates a zip file with YAML translation files (translation file per domain).
     * @see TranslationArchiveGenerator::SMALL_TRANSLATION_SET a small translation set (messages and validation domains)
     */
    public static function createTranslationsZip(
        string $filePath,
        string $languageCode,
        ?array $translations = null
    ): void {
        $zip = new \ZipArchive();
        $zip->open($filePath, \ZipArchive::CREATE);

        if (null === $translations) {
            $translations = static::SMALL_TRANSLATION_SET;
        }

        foreach ($translations as $domain => $data) {
            $content = '';
            foreach ($data as $key => $value) {
                $content .= "$key: $value\n";
            }
            $zip->addFromString("$domain.$languageCode.yml", $content);
        }
        $zip->close();
    }
}

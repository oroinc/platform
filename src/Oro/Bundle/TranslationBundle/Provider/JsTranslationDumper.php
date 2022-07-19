<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Dump JS translations to files.
 */
class JsTranslationDumper
{
    private JsTranslationGenerator $generator;
    private LanguageProvider $languageProvider;
    private FileManager $fileManager;

    public function __construct(
        JsTranslationGenerator $generator,
        LanguageProvider $languageProvider,
        FileManager $fileManager
    ) {
        $this->generator = $generator;
        $this->languageProvider = $languageProvider;
        $this->fileManager = $fileManager;
    }

    /**
     * @return string[]
     */
    public function getAllLocales(): array
    {
        return $this->languageProvider->getAvailableLanguageCodes();
    }

    /**
     * @param string[] $locales
     */
    public function dumpTranslations(array $locales = []): void
    {
        if (!$locales) {
            $locales = $this->getAllLocales();
        }
        foreach ($locales as $locale) {
            $this->dumpTranslationFile($locale);
        }
    }

    public function dumpTranslationFile(string $locale): string
    {
        $translationFile = $this->getTranslationFilePath($locale);
        try {
            $content = $this->generator->generateJsTranslations($locale);
            $this->fileManager->writeToStorage($content, $translationFile);
        } catch (\Exception $e) {
            $message = sprintf(
                'An error occurred while dumping content to %s, %s',
                $translationFile,
                $e->getMessage()
            );

            throw new IOException($message, $e->getCode(), $e);
        }

        return $translationFile;
    }

    public function isTranslationFileExist(string $locale): bool
    {
        return $this->fileManager->hasFile($this->getTranslationFilePath($locale));
    }

    private function getTranslationFilePath(string $locale): string
    {
        return sprintf('translation/%s.json', $locale);
    }
}

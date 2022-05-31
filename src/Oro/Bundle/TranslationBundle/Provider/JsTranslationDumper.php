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
        if (false === @file_put_contents($translationFile, $this->generator->generateJsTranslations($locale))) {
            throw new IOException(sprintf('Unable to write file %s.', $translationFile));
        }

        return $translationFile;
    }

    public function isTranslationFileExist(string $locale): bool
    {
        return file_exists($this->getTranslationFilePath($locale));
    }

    private function getTranslationFilePath(string $locale): string
    {
        return $this->fileManager->getFilePath(sprintf('translation/%s.json', $locale));
    }
}

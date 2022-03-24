<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Dump JS translations to files.
 */
class JsTranslationDumper implements LoggerAwareInterface
{
    private JsTranslationGenerator $generator;
    private LanguageProvider $languageProvider;
    private FileManager $fileManager;
    private LoggerInterface $logger;

    public function __construct(
        JsTranslationGenerator $generator,
        LanguageProvider $languageProvider,
        FileManager $fileManager
    ) {
        $this->generator = $generator;
        $this->languageProvider = $languageProvider;
        $this->fileManager = $fileManager;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string[] $locales
     *
     * @return bool
     */
    public function dumpTranslations(array $locales = []): bool
    {
        if (empty($locales)) {
            $locales = $this->languageProvider->getAvailableLanguageCodes();
        }

        foreach ($locales as $locale) {
            $target = $this->getTranslationFilePath($locale);
            $this->logger->info('<info>[file+]</info> ' . $target);
            if (false === @file_put_contents($target, $this->generator->generateJsTranslations($locale))) {
                throw new IOException('Unable to write file ' . $target);
            }
        }

        return true;
    }

    public function isTranslationFileExist(string $locale): bool
    {
        $translationFilePath = $this->getTranslationFilePath($locale);

        return file_exists($translationFilePath);
    }

    private function getTranslationFilePath(string $locale): string
    {
        return $this->fileManager->getFilePath(sprintf('translation/%s.json', $locale));
    }
}

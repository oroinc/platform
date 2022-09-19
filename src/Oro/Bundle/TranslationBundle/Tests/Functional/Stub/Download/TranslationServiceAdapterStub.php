<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Download;

use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The stub of TranslationServiceAdapterInterface that work with local files.
 */
class TranslationServiceAdapterStub implements TranslationServiceAdapterInterface
{
    private string $archivePath;

    public function __construct(string $archivePath)
    {
        $this->archivePath = $archivePath;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchTranslationMetrics(): array
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function downloadLanguageTranslationsArchive(string $languageCode, string $pathToSaveDownloadedArchive): void
    {
        $filesystem = new Filesystem();
        $filesystem->copy($this->archivePath, $pathToSaveDownloadedArchive);
    }

    /**
     * {@inheritDoc}
     */
    public function extractTranslationsFromArchive(
        string $pathToArchive,
        string $directoryPathToExtractTo,
        string $languageCode
    ): void {
        $zip = new \ZipArchive();
        $zip->open($pathToArchive);
        $zip->extractTo($directoryPathToExtractTo);
        $zip->close();
    }
}

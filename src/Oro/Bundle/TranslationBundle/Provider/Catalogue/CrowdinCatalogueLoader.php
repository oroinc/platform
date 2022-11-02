<?php

namespace Oro\Bundle\TranslationBundle\Provider\Catalogue;

use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReader;

/**
 * Catalogue loader that gets the translation catalogue from the Crowdin.
 */
class CrowdinCatalogueLoader implements CatalogueLoaderInterface
{
    private TranslationServiceAdapterInterface $translationServiceAdapter;
    private TranslationReader $translationReader;

    public function __construct(
        TranslationServiceAdapterInterface $translationServiceAdapter,
        TranslationReader                  $translationReader
    ) {
        $this->translationServiceAdapter = $translationServiceAdapter;
        $this->translationReader = $translationReader;
    }

    /**
     * {@inheritDoc}
     */
    public function getLoaderName(): string
    {
        return 'crowdin';
    }

    /**
     * {@inheritDoc}
     */
    public function getCatalogue(string $locale): MessageCatalogue
    {
        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $filesPath = $tmpDir . 'unpacked';
        $archivePath = $tmpDir . 'temp.zip';

        $this->translationServiceAdapter->downloadLanguageTranslationsArchive($locale, $archivePath);

        // We treat "en_US" and "en" as the same language, and we use "en" to designate its translations internally.
        // It may be removed once the default locale is changed to 'en_US" (BB-19560).
        if ('en' === $locale) {
            $this->translationServiceAdapter->extractTranslationsFromArchive($archivePath, $filesPath, 'en_US');
            // renames all *.en_US.csv translation files to *.en.csv
            $this->changeFileExtensions('.en_US.csv', '.en.csv', $filesPath);
            $this->changeFileExtensions('.en_US.yml', '.en.yml', $filesPath);
        } else {
            $this->translationServiceAdapter->extractTranslationsFromArchive($archivePath, $filesPath, $locale);
        }

        $catalogue = new MessageCatalogue($locale);
        $this->translationReader->read($filesPath, $catalogue);
        unlink($archivePath);
        $this->removeDirectory($filesPath);

        return $catalogue;
    }

    private function removeDirectory(string $targetDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            $path->isFile() ? \unlink($path->getPathname()) : \rmdir($path->getPathname());
        }

        \rmdir($targetDir);
    }

    private function changeFileExtensions(string $currentExtension, string $newExtension, string $inTheDirectory): void
    {
        $finder = Finder::create()->files()->name('*' . $currentExtension)->in($inTheDirectory);

        foreach ($finder->files() as $file) {
            \rename($file->getRealPath(), \str_replace($currentExtension, $newExtension, $file->getRealPath()));
        }
    }
}

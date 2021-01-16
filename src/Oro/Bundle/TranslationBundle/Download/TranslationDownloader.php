<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Download;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Exception\TranslationDatabasePersisterException;
use Oro\Bundle\TranslationBundle\Exception\TranslationDownloaderException;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceAdapterException;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Component\Log\LogAndThrowExceptionTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReader;

/**
 * Downloads translation files from the translation service and applies them to the application.
 */
class TranslationDownloader
{
    use LogAndThrowExceptionTrait;

    private TranslationServiceAdapterInterface $translationServiceAdapter;
    private TranslationMetricsProviderInterface $translationMetricsProvider;
    private JsTranslationDumper $jsTranslationDumper;
    private TranslationReader $translationReader;
    private DatabasePersister $databasePersister;
    private ManagerRegistry $doctrine;
    private string $kernelCacheDir;
    private LoggerInterface $logger; // used by a trait

    /**
     * Please note that it will also set the provided logger (or a new instance of NullLogger if none provided)
     * on both $translationServiceAdapter and $translationReader.
     */
    public function __construct(
        TranslationServiceAdapterInterface $translationServiceAdapter,
        TranslationMetricsProviderInterface $translationMetricsProvider,
        JsTranslationDumper $jsTranslationDumper,
        TranslationReader $translationReader,
        DatabasePersister $translationDatabasePersister,
        ManagerRegistry $doctrine,
        string $kernelCacheDir,
        ?LoggerInterface $logger = null
    ) {
        $this->translationServiceAdapter = $translationServiceAdapter;
        $this->translationMetricsProvider = $translationMetricsProvider;
        $this->jsTranslationDumper = $jsTranslationDumper;
        $this->translationReader = $translationReader;
        $this->databasePersister = $translationDatabasePersister;
        $this->doctrine = $doctrine;
        $this->kernelCacheDir = $kernelCacheDir;

        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * Fetches translation metrics for the specified language from the translation metrics provider.
     *
     * If the specified language is available, the translation metrics are returned as an array:
     * <code>
     *     [
     *         'code' => 'uk_UA',                       // full language code, including locality
     *         'altCode' => 'uk',                       // optional, may not be present
     *         'translationStatus' => 30,               // percentage of translated strings or words (varies by service)
     *         'lastBuildDate' => \DateTimeInterface    // object with the last translation build date
     *     ]
     * </code>
     *
     * If the specified language is not available on the translation service, this method will return null.
     */
    public function fetchLanguageMetrics(string $languageCode): ?array
    {
        return $this->translationMetricsProvider->getForLanguage($languageCode);
    }

    /**
     * Downloads and applies (saves to the database) translations for the specified language.
     *
     * @throws TranslationDownloaderException if the specified language is not added,
     *                                     or if fails to update the language record after saving the translations.
     * @throws TranslationServiceAdapterException if fails to download or extract translations.
     * @throws TranslationDatabasePersisterException if fails to save translations to the database.
     */
    public function downloadAndApplyTranslations(string $languageCode): void
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass(Language::class);
        /** @var LanguageRepository $languageRepository */
        $languageRepository = $em->getRepository(Language::class);

        $language = $languageRepository->findOneBy(['code' => $languageCode]);
        if (null === $language) {
            $this->throwErrorException(
                TranslationDownloaderException::class,
                'Language with code "{language_code}" should be added first.',
                ['language_code' => $languageCode]
            );
        }

        $metrics = $this->fetchLanguageMetrics($languageCode);
        if (null === $metrics) {
            $this->throwErrorException(
                TranslationDownloaderException::class,
                'No available translations found for "{language_code}".',
                ['language_code' => $languageCode]
            );
        }

        $pathToSave = $this->getTmpDir('download_') . \DIRECTORY_SEPARATOR . $languageCode;
        $this->downloadTranslationsArchive($languageCode, $pathToSave);
        $this->loadTranslationsFromArchive($pathToSave, $languageCode);

        $language->setInstalledBuildDate($metrics['lastBuildDate']);

        try {
            $em->flush($language);
        } catch (ORMException $e) {
            $this->throwErrorException(
                TranslationDownloaderException::class,
                'Cannot update installed build date for "{language_code}".',
                ['language_code' => $languageCode],
                $e
            );
        }
    }

    /**
     * Downloads an archive with translations for a language and saves it at the specified path.
     *
     * @param string $languageCode full language code, including locality, e.g. "en_US", "en_GB"
     * @param string $filePathToSaveDownloadedArchive
     *
     * @throws TranslationServiceAdapterException if fails to download translations or to save the archive file
     */
    public function downloadTranslationsArchive(
        string $languageCode,
        string $filePathToSaveDownloadedArchive
    ): void {
        $this->translationServiceAdapter->downloadLanguageTranslationsArchive(
            $languageCode,
            $filePathToSaveDownloadedArchive,
        );
    }

    /**
     * Extracts translations from the archive and saves them to the database.
     *
     * Please note that when requesting to load translations for "en_US" language they will be saved
     * to the database as translations for "en" language as "en_US" and "en" are treated as the same
     * (default) language internally.
     *
     * @throws TranslationServiceAdapterException if fails to extract translations from the archive
     * @throws TranslationDatabasePersisterException if fails to save the translations to the database
     */
    public function loadTranslationsFromArchive(string $pathToArchiveFile, string $languageCode): void
    {
        $targetDir = $this->getTmpDir('extracted_' . $languageCode);

        $this->translationServiceAdapter->extractTranslationsFromArchive($pathToArchiveFile, $targetDir);

        // We treat "en_US" and "en" as the same language and we use "en" to designate its translations internally.
        // It may be removed once the default locale is changed to 'en_US" (BB-19560).
        if ('en' === $languageCode) {
            // renames all *.en_US.yml translation files to *.en.yml
            $this->changeFileExtensions('.en_US.yml', '.en.yml', $targetDir);
        }

        $this->saveTranslationsToDatabase($languageCode, $targetDir);
        $this->jsTranslationDumper->dumpTranslations([$languageCode]);
        $this->removeDirectory($targetDir);
    }

    /**
     * Creates a temporary directory with a unique name and returns its path.
     *
     * @throws TranslationDownloaderException if the directory cannot be created
     */
    public function getTmpDir(string $prefix): string
    {
        $pathParts = [
            \rtrim($this->kernelCacheDir, \DIRECTORY_SEPARATOR),
            'translations',
            \ltrim(\uniqid($prefix, false), \DIRECTORY_SEPARATOR)
        ];
        $path = \implode(\DIRECTORY_SEPARATOR, $pathParts);

        try {
            if (!\is_dir($path) && !\mkdir($path, 0777, true) && !\is_dir($path)) {
                $this->throwErrorException(
                    TranslationDownloaderException::class,
                    'Directory "{path}" cannot be created or accessed.',
                    ['path' => $path]
                );
            }
        } catch (\Throwable $e) {
            $this->throwErrorException(
                TranslationDownloaderException::class,
                'Directory "{path}" cannot be created or accessed.',
                ['path' => $path]
            );
        }

        return $path;
    }

    private function changeFileExtensions(string $currentExtension, string $newExtension, string $inTheDirectory): void
    {
        $finder = Finder::create()->files()->name('*' . $currentExtension)->in($inTheDirectory);

        foreach ($finder->files() as $file) {
            \rename($file->getRealPath(), \str_replace($currentExtension, $newExtension, $file->getRealPath()));
        }
    }

    /**
     * Removes the directory even if it is not empty.
     */
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

    /**
     * Reads translations from (already extracted) files and saves them to the database.
     *
     * @throws TranslationDatabasePersisterException if fails to write data to the database
     */
    private function saveTranslationsToDatabase(string $languageCode, string $sourceDir): void
    {
        $catalog = new MessageCatalogue($languageCode);
        $this->translationReader->read($sourceDir, $catalog);

        try {
            $this->databasePersister->persist($languageCode, $catalog->all());
        } catch (\Exception $e) {
            // This conversion may be removed after the database persister is refactored
            // to throw proper exception type on its own.
            if ($e instanceof TranslationDatabasePersisterException) {
                throw $e;
            }
            throw new TranslationDatabasePersisterException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Additionally calls $this->translationServiceAdapter->setLogger() and $this->jsTranslationDumper->setLogger()
     * with the provided logger instance.
     */
    private function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        if ($this->translationServiceAdapter instanceof LoggerAwareInterface) {
            $this->translationServiceAdapter->setLogger($this->logger);
        }
        if ($this->jsTranslationDumper instanceof LoggerAwareInterface) {
            $this->jsTranslationDumper->setLogger($this->logger);
        }
    }
}

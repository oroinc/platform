<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Download;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Exception\TranslationDownloaderException;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Component\Log\Test\LogAndThrowExceptionTestTrait;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Reader\TranslationReader;

/** @coversDefaultClass \Oro\Bundle\TranslationBundle\Download\TranslationDownloader */
class TranslationDownloaderTest extends \PHPUnit\Framework\TestCase
{
    use LogAndThrowExceptionTestTrait;
    use TempDirExtension;

    /**
     * This translations sample is here just for confirmation that YamlFixer that was used in 4.1 and earlier,
     * is not needed anymore.
     */
    private const WEIRD_YAML_TRANSLATIONS = <<<'YAML'
---
test: test
test1:
test %num%: tessto presto %num% #%num%
multi: |-
  Test l'aide <a href="http://community.oroinc.com"> forums</a>.
  Second line.
another one: another one string
'test32': "test3223"
'escaping ''single'' quotes': escaping 'single' quotes
"escaping \"double\" quotes": escaping "double" quotes
YAML
    ;

    private const PARSED_YAML_TRANSLATIONS = [
        'test' => 'test',
        'test1' => null,
        'test %num%' => 'tessto presto %num%',
        'multi' => "Test l'aide <a href=\"http://community.oroinc.com\"> forums</a>.\nSecond line.",
        'another one' => 'another one string',
        'test32' => 'test3223',
        "escaping 'single' quotes" => "escaping 'single' quotes",
        'escaping "double" quotes' => 'escaping "double" quotes',
    ];

    private TranslationServiceAdapterInterface $translationServiceAdapter;
    private TranslationMetricsProviderInterface $translationMetricsProvider;
    private JsTranslationDumper $jsTranslationDumper;
    private TranslationReader $translationReader;
    private TranslationDownloader $downloader;
    private DatabasePersister $databasePersister;
    private ManagerRegistry $doctrine;
    private LoggerInterface $logger;

    private string $className = TranslationDownloader::class;
    private string $kernelCacheDir;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->translationServiceAdapter = $this->createMock(TranslationServiceAdapterInterface::class);
        $this->translationMetricsProvider = $this->createMock(TranslationMetricsProviderInterface::class);
        $this->jsTranslationDumper  = $this->createMock(JsTranslationDumper::class);
        $this->translationReader = new TranslationReader();
        $this->translationReader->addLoader('yml', new YamlFileLoader());
        $this->databasePersister = $this->createMock(DatabasePersister::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->kernelCacheDir = $this->getTempDir('trans');
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->downloader = new TranslationDownloader(
            $this->translationServiceAdapter,
            $this->translationMetricsProvider,
            $this->jsTranslationDumper,
            $this->translationReader,
            $this->databasePersister,
            $this->doctrine,
            $this->kernelCacheDir,
            $this->logger
        );
    }

    public function testFetchLanguageMetrics(): void
    {
        $languageCode = 'uk_UA';
        $metrics = [
            'code' => 'uk_UA',
            'altCode' => 'uk',
            'translationStatus' => 30,
            'lastBuildDate' => new \DateTime()
        ];

        $this->translationMetricsProvider->expects(static::once())
            ->method('getForLanguage')
            ->with($languageCode)
            ->willReturn($metrics);

        static::assertSame($metrics, $this->downloader->fetchLanguageMetrics($languageCode));
    }

    /** @covers ::downloadAndApplyTranslations */
    public function testDownloadAndApplyTranslations(): void
    {
        $languageCode = 'uk_UA';
        $languageEntity = new Language();
        $lastBuildDate = new \DateTime();
        $metrics = [
            'code' => 'uk_UA',
            'altCode' => 'uk',
            'translationStatus' => 30,
            'lastBuildDate' => $lastBuildDate
        ];

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->method('getManagerForClass')->willReturnMap([[Language::class, $em]]);
        $repository = $this->createMock(LanguageRepository::class);
        $em->method('getRepository')->willReturnMap([[Language::class, $repository]]);
        $this->translationMetricsProvider->method('getForLanguage')->willReturnMap([[$languageCode, $metrics]]);

        $repository->expects(static::once())
            ->method('findOneBy')
            ->with(['code' => $languageCode])
            ->willReturn($languageEntity);

        $this->translationServiceAdapter->expects(static::once())
            ->method('downloadLanguageTranslationsArchive')
            ->with($languageCode, static::isType('string'));

        $this->jsTranslationDumper->expects(static::once())
            ->method('dumpTranslations')
            ->with([$languageCode]);

        $em->expects(static::once())
            ->method('flush')
            ->with(static::callback(static fn (Language $lang) => $lang->getInstalledBuildDate() === $lastBuildDate));

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    /** @covers ::downloadAndApplyTranslations */
    public function testDownloadAndApplyTranslationsThrowsExceptionIfLanguageIsNotAdded(): void
    {
        $languageCode = 'uk_UA';

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->method('getManagerForClass')->willReturnMap([[Language::class, $em]]);
        $repository = $this->createMock(LanguageRepository::class);
        $em->method('getRepository')->willReturnMap([[Language::class, $repository]]);
        $repository->method('findOneBy')->willReturn(null);

        $this->expectThrowErrorException(
            TranslationDownloaderException::class,
            'Language with code "' . $languageCode . '" should be added first.',
            'Language with code "{language_code}" should be added first.',
            ['language_code' => $languageCode]
        );

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    /** @covers ::downloadAndApplyTranslations */
    public function testDownloadAndApplyTranslationsThrowsExceptionIfNoTranslationsForLanguage(): void
    {
        $languageCode = 'uk_UA';
        $languageEntity = new Language();

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->method('getManagerForClass')->willReturnMap([[Language::class, $em]]);
        $repository = $this->createMock(LanguageRepository::class);
        $em->method('getRepository')->willReturnMap([[Language::class, $repository]]);
        $repository->method('findOneBy')->willReturnMap([[['code' => $languageCode], null, $languageEntity]]);
        $this->translationMetricsProvider->method('getForLanguage')->willReturn(null);

        $this->expectThrowErrorException(
            TranslationDownloaderException::class,
            'No available translations found for "' . $languageCode . '".',
            'No available translations found for "{language_code}".',
            ['language_code' => $languageCode]
        );

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    /** @covers ::downloadAndApplyTranslations */
    public function testDownloadAndApplyTranslationsThrowsExceptionIfCannotUpdateInstalledBuildDate(): void
    {
        $languageCode = 'uk_UA';
        $languageEntity = new Language();
        $lastBuildDate = new \DateTime();
        $metrics = [
            'code' => 'uk_UA',
            'altCode' => 'uk',
            'translationStatus' => 30,
            'lastBuildDate' => $lastBuildDate
        ];

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->method('getManagerForClass')->willReturnMap([[Language::class, $em]]);
        $repository = $this->createMock(LanguageRepository::class);
        $em->method('getRepository')->willReturnMap([[Language::class, $repository]]);
        $repository->method('findOneBy')->willReturnMap([[['code' => $languageCode], null, $languageEntity]]);
        $this->translationMetricsProvider->method('getForLanguage')->willReturnMap([[$languageCode, $metrics]]);

        $ormException = new ORMException('text', 12345);
        $em->method('flush')->willThrowException($ormException);

        $this->expectThrowErrorException(
            TranslationDownloaderException::class,
            'Cannot update installed build date for "' . $languageCode . '".',
            'Cannot update installed build date for "{language_code}".',
            ['language_code' => $languageCode],
            $ormException
        );

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    public function testDownloadTranslationsArchive(): void
    {
        $languageCode = 'uk_UA';
        $filePathToSaveDownloadedArchive = \uniqid('', false);

        $this->translationServiceAdapter->expects(static::once())
            ->method('downloadLanguageTranslationsArchive')
            ->with($languageCode, $filePathToSaveDownloadedArchive);

        $this->downloader->downloadTranslationsArchive($languageCode, $filePathToSaveDownloadedArchive);
    }

    /**
     * @covers ::loadTranslationsFromArchive
     * @covers ::saveTranslationsToDatabase
     * @covers ::removeDirectory
     */
    public function testLoadTranslationsFromArchive(): void
    {
        $pathToArchiveFile = $this->getTempFile('archive_');
        $langCode = 'uk_UA';
        $expectedPart = \DIRECTORY_SEPARATOR . 'extracted_' . $langCode;
        $expectedTranslations = [
            'messages' => self::PARSED_YAML_TRANSLATIONS,
            'validation' => ['key1' => 'tr1', 'key2' => 'tr2']
        ];
        $rememberedDir = '';

        $this->translationServiceAdapter->expects(static::once())
            ->method('extractTranslationsFromArchive')
            ->with(
                $pathToArchiveFile,
                static::callback(
                    static function (string $directoryPathToExtractTo) use ($langCode, $expectedPart, &$rememberedDir) {
                        $rememberedDir = $directoryPathToExtractTo;
                        \file_put_contents(
                            $directoryPathToExtractTo . \DIRECTORY_SEPARATOR . "messages.$langCode.yml",
                            self::WEIRD_YAML_TRANSLATIONS
                        );
                        \file_put_contents(
                            $directoryPathToExtractTo . \DIRECTORY_SEPARATOR . "validation.$langCode.yml",
                            "'key1': tr1\n'key2': tr2"
                        );
                        return false !== \strpos($directoryPathToExtractTo, $expectedPart);
                    }
                )
            );

        $this->databasePersister->expects(static::once())
            ->method('persist')
            ->with($langCode, $expectedTranslations, Translation::SCOPE_INSTALLED);
        $this->jsTranslationDumper->expects(static::once())
            ->method('dumpTranslations')
            ->with([$langCode]);

        $this->downloader->loadTranslationsFromArchive($pathToArchiveFile, $langCode);

        static::assertFileDoesNotExist($rememberedDir . \DIRECTORY_SEPARATOR . "messages.$langCode.yml");
        static::assertFileDoesNotExist($rememberedDir . \DIRECTORY_SEPARATOR . "validation.$langCode.yml");
        static::assertDirectoryDoesNotExist($rememberedDir);
    }

    /**
     * @covers ::loadTranslationsFromArchive
     * @covers ::changeFileExtensions
     */
    public function testLoadTranslationsFromArchiveLoadsEnUsAsEn(): void
    {
        $pathToArchiveFile = $this->getTempFile('archive_');
        $expectedPart = \DIRECTORY_SEPARATOR . 'extracted_en';
        $expectedTranslations = [
            'messages' => self::PARSED_YAML_TRANSLATIONS,
            'validation' => ['key1' => 'tr1', 'key2' => 'tr2']
        ];
        $rememberedDir = '';

        $this->translationServiceAdapter->expects(static::once())
            ->method('extractTranslationsFromArchive')
            ->with(
                $pathToArchiveFile,
                static::callback(
                    static function (string $directoryPathToExtractTo) use ($expectedPart, &$rememberedDir) {
                        $rememberedDir = $directoryPathToExtractTo;
                        \file_put_contents(
                            $directoryPathToExtractTo . \DIRECTORY_SEPARATOR . 'messages.en_US.yml',
                            self::WEIRD_YAML_TRANSLATIONS
                        );
                        \file_put_contents(
                            $directoryPathToExtractTo . \DIRECTORY_SEPARATOR . 'validation.en_US.yml',
                            "'key1': tr1\n'key2': tr2"
                        );
                        return false !== \strpos($directoryPathToExtractTo, $expectedPart);
                    }
                )
            );

        $this->databasePersister->expects(static::once())
            ->method('persist')
            ->with('en', $expectedTranslations, Translation::SCOPE_INSTALLED);
        $this->jsTranslationDumper->expects(static::once())
            ->method('dumpTranslations')
            ->with(['en']);

        $this->downloader->loadTranslationsFromArchive($pathToArchiveFile, 'en');

        static::assertFileDoesNotExist($rememberedDir . \DIRECTORY_SEPARATOR . 'messages.en.yml');
        static::assertFileDoesNotExist($rememberedDir . \DIRECTORY_SEPARATOR . 'validation.en.yml');
        static::assertDirectoryDoesNotExist($rememberedDir);
    }

    /** @covers ::getTmpDir */
    public function testGetTmpDir(): void
    {
        $expectedParent = $this->kernelCacheDir
            . \DIRECTORY_SEPARATOR . 'translations'
            . \DIRECTORY_SEPARATOR . 'test_prefix';

        $dir = $this->downloader->getTmpDir('test_prefix');

        static::assertStringStartsWith($expectedParent, $dir);
        static::assertDirectoryIsReadable($dir);
    }

    /** @covers ::getTmpDir */
    public function testGetTmpDirThrowsExceptionIfDirectoryCannotBeCreated(): void
    {
        $readOnlyDir = $this->getTempDir('read_only_directory');
        if (false === \chmod($readOnlyDir, 0555)) {
            static::fail('Cannot set the directory permissions necessary for this test.');
        }

        $downloader = new TranslationDownloader(
            $this->translationServiceAdapter,
            $this->translationMetricsProvider,
            $this->jsTranslationDumper,
            $this->translationReader,
            $this->databasePersister,
            $this->doctrine,
            $readOnlyDir,
            $this->logger
        );

        $this->expectException(TranslationDownloaderException::class);
        $this->expectExceptionMessageMatches('/Directory "(.+)" cannot be created or accessed./');
        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                'Directory "{path}" cannot be created or accessed.',
                static::logicalAnd(
                    static::isType('array'),
                    static::arrayHasKey('path'),
                    static::arrayHasKey('called_in')
                )
            );

        try {
            $downloader->getTmpDir('test_prefix');
        } finally {
            \chmod($readOnlyDir, 0755);
        }
    }
}

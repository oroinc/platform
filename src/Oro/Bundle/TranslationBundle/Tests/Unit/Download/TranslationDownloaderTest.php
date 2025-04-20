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
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Reader\TranslationReader;

/** @coversDefaultClass \Oro\Bundle\TranslationBundle\Download\TranslationDownloader */
class TranslationDownloaderTest extends \PHPUnit\Framework\TestCase
{
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
        'test %num%' => 'tessto presto %num%',
        'multi' => "Test l'aide <a href=\"http://community.oroinc.com\"> forums</a>.\nSecond line.",
        'another one' => 'another one string',
        'test32' => 'test3223',
        "escaping 'single' quotes" => "escaping 'single' quotes",
        'escaping "double" quotes' => 'escaping "double" quotes',
    ];

    /** @var TranslationServiceAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translationServiceAdapter;

    /** @var TranslationMetricsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translationMetricsProvider;

    /** @var JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject */
    private $jsTranslationDumper;

    /** @var TranslationReader */
    private $translationReader;

    /** @var DatabasePersister|\PHPUnit\Framework\MockObject\MockObject */
    private $databasePersister;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var TranslationDownloader */
    private $downloader;

    private string $kernelCacheDir;

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

        $this->translationMetricsProvider->expects(self::once())
            ->method('getForLanguage')
            ->with($languageCode)
            ->willReturn($metrics);

        self::assertSame($metrics, $this->downloader->fetchLanguageMetrics($languageCode));
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
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($em);
        $repository = $this->createMock(LanguageRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($repository);
        $this->translationMetricsProvider->expects(self::once())
            ->method('getForLanguage')
            ->with($languageCode)
            ->willReturn($metrics);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $languageCode])
            ->willReturn($languageEntity);

        $this->translationServiceAdapter->expects(self::once())
            ->method('downloadLanguageTranslationsArchive')
            ->with($languageCode, self::isType('string'));

        $this->jsTranslationDumper->expects(self::once())
            ->method('dumpTranslations')
            ->with([$languageCode]);

        $em->expects(self::once())
            ->method('flush')
            ->with(self::callback(static fn (Language $lang) => $lang->getInstalledBuildDate() === $lastBuildDate));

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    /** @covers ::downloadAndApplyTranslations */
    public function testDownloadAndApplyTranslationsThrowsExceptionIfLanguageIsNotAdded(): void
    {
        $languageCode = 'uk_UA';

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($em);
        $repository = $this->createMock(LanguageRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->expectException(TranslationDownloaderException::class);
        $this->expectExceptionMessage('Language with code "' . $languageCode . '" should be added first.');
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Language with code "{language_code}" should be added first.',
                self::logicalAnd(
                    self::isType('array'),
                    self::arrayHasKey('language_code'),
                    self::callback(fn (array $val) => $val['language_code'] === $languageCode),
                    self::arrayHasKey('called_in')
                )
            );

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    /** @covers ::downloadAndApplyTranslations */
    public function testDownloadAndApplyTranslationsThrowsExceptionIfNoTranslationsForLanguage(): void
    {
        $languageCode = 'uk_UA';
        $languageEntity = new Language();

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($em);
        $repository = $this->createMock(LanguageRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $languageCode])
            ->willReturn($languageEntity);
        $this->translationMetricsProvider->expects(self::once())
            ->method('getForLanguage')
            ->willReturn(null);

        $this->expectException(TranslationDownloaderException::class);
        $this->expectExceptionMessage('No available translations found for "' . $languageCode . '".');
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'No available translations found for "{language_code}".',
                self::logicalAnd(
                    self::isType('array'),
                    self::arrayHasKey('language_code'),
                    self::callback(fn (array $val) => $val['language_code'] === $languageCode),
                    self::arrayHasKey('called_in')
                )
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
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($em);
        $repository = $this->createMock(LanguageRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $languageCode])
            ->willReturn($languageEntity);
        $this->translationMetricsProvider->expects(self::once())
            ->method('getForLanguage')
            ->with($languageCode)
            ->willReturn($metrics);

        $ormException = new ORMException('text', 12345);
        $em->expects(self::once())
            ->method('flush')
            ->willThrowException($ormException);

        $this->expectException(TranslationDownloaderException::class);
        $this->expectExceptionMessage('Cannot update installed build date for "' . $languageCode . '".');
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Cannot update installed build date for "{language_code}".',
                self::logicalAnd(
                    self::isType('array'),
                    self::arrayHasKey('language_code'),
                    self::callback(fn (array $val) => $val['language_code'] === $languageCode),
                    self::arrayHasKey('called_in'),
                    self::arrayHasKey('exception'),
                    self::callback(fn (array $val) => $val['exception'] === $ormException)
                )
            );

        $this->downloader->downloadAndApplyTranslations($languageCode);
    }

    public function testDownloadTranslationsArchive(): void
    {
        $languageCode = 'uk_UA';
        $filePathToSaveDownloadedArchive = uniqid('', false);

        $this->translationServiceAdapter->expects(self::once())
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
        $expectedPart = DIRECTORY_SEPARATOR . 'extracted_' . $langCode;
        $expectedTranslations = [
            'messages' => self::PARSED_YAML_TRANSLATIONS,
            'validation' => ['key1' => 'tr1', 'key2' => 'tr2']
        ];
        $rememberedDir = '';

        $this->translationServiceAdapter->expects(self::once())
            ->method('extractTranslationsFromArchive')
            ->with(
                $pathToArchiveFile,
                self::callback(
                    static function (string $directoryPathToExtractTo) use ($langCode, $expectedPart, &$rememberedDir) {
                        $rememberedDir = $directoryPathToExtractTo;
                        file_put_contents(
                            $directoryPathToExtractTo . DIRECTORY_SEPARATOR . "messages.$langCode.yml",
                            self::WEIRD_YAML_TRANSLATIONS
                        );
                        file_put_contents(
                            $directoryPathToExtractTo . DIRECTORY_SEPARATOR . "validation.$langCode.yml",
                            "'key1': tr1\n'key2': tr2"
                        );
                        return str_contains($directoryPathToExtractTo, $expectedPart);
                    }
                )
            );

        $this->databasePersister->expects(self::once())
            ->method('persist')
            ->with($langCode, $expectedTranslations);
        $this->jsTranslationDumper->expects(self::once())
            ->method('dumpTranslations')
            ->with([$langCode]);

        $this->downloader->loadTranslationsFromArchive($pathToArchiveFile, $langCode);

        self::assertFileDoesNotExist($rememberedDir . DIRECTORY_SEPARATOR . "messages.$langCode.yml");
        self::assertFileDoesNotExist($rememberedDir . DIRECTORY_SEPARATOR . "validation.$langCode.yml");
        self::assertDirectoryDoesNotExist($rememberedDir);
    }

    /**
     * @covers ::loadTranslationsFromArchive
     * @covers ::changeFileExtensions
     */
    public function testLoadTranslationsFromArchiveLoadsEnUsAsEn(): void
    {
        $pathToArchiveFile = $this->getTempFile('archive_');
        $expectedPart = DIRECTORY_SEPARATOR . 'extracted_en';
        $expectedTranslations = [
            'messages' => self::PARSED_YAML_TRANSLATIONS,
            'validation' => ['key1' => 'tr1', 'key2' => 'tr2']
        ];
        $rememberedDir = '';

        $this->translationServiceAdapter->expects(self::once())
            ->method('extractTranslationsFromArchive')
            ->with(
                $pathToArchiveFile,
                self::callback(
                    static function (string $directoryPathToExtractTo) use ($expectedPart, &$rememberedDir) {
                        $rememberedDir = $directoryPathToExtractTo;
                        file_put_contents(
                            $directoryPathToExtractTo . DIRECTORY_SEPARATOR . 'messages.en_US.yml',
                            self::WEIRD_YAML_TRANSLATIONS
                        );
                        file_put_contents(
                            $directoryPathToExtractTo . DIRECTORY_SEPARATOR . 'validation.en_US.yml',
                            "'key1': tr1\n'key2': tr2"
                        );
                        return str_contains($directoryPathToExtractTo, $expectedPart);
                    }
                )
            );

        $this->databasePersister->expects(self::once())
            ->method('persist')
            ->with('en', $expectedTranslations, Translation::SCOPE_INSTALLED);
        $this->jsTranslationDumper->expects(self::once())
            ->method('dumpTranslations')
            ->with(['en']);

        $this->downloader->loadTranslationsFromArchive($pathToArchiveFile, 'en');

        self::assertFileDoesNotExist($rememberedDir . DIRECTORY_SEPARATOR . 'messages.en.yml');
        self::assertFileDoesNotExist($rememberedDir . DIRECTORY_SEPARATOR . 'validation.en.yml');
        self::assertDirectoryDoesNotExist($rememberedDir);
    }

    /** @covers ::getTmpDir */
    public function testGetTmpDir(): void
    {
        $expectedParent = $this->kernelCacheDir
            . DIRECTORY_SEPARATOR . 'translations'
            . DIRECTORY_SEPARATOR . 'test_prefix';

        $dir = $this->downloader->getTmpDir('test_prefix');

        self::assertStringStartsWith($expectedParent, $dir);
        self::assertDirectoryIsReadable($dir);
    }

    /** @covers ::getTmpDir */
    public function testGetTmpDirThrowsExceptionIfDirectoryCannotBeCreated(): void
    {
        $readOnlyDir = $this->getTempDir('read_only_directory');
        if (false === chmod($readOnlyDir, 0555)) {
            self::fail('Cannot set the directory permissions necessary for this test.');
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
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Directory "{path}" cannot be created or accessed.',
                self::logicalAnd(
                    self::isType('array'),
                    self::arrayHasKey('path'),
                    self::arrayHasKey('called_in')
                )
            );

        try {
            $downloader->getTmpDir('test_prefix');
        } finally {
            chmod($readOnlyDir, 0755);
        }
    }
}

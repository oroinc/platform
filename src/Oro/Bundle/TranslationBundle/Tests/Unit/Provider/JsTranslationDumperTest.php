<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Gaufrette\Filesystem;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\TranslationBundle\Controller\Controller;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\Constraint\IsEqual;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class JsTranslationDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const TRANSLATION_DOMAINS = ['jsmessages'];

    /** @var Controller|\PHPUnit\Framework\MockObject\MockObject */
    private $translationController;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private static $fileManager;

    /** @var JsTranslationDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->translationController = $this->createMock(Controller::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        self::$fileManager = new FileManager('js_trans_dumper');
        self::$fileManager->setProtocol('gaufrette');
        self::$fileManager->setFilesystemMap(new FilesystemMap([
            'js_trans_dumper' => new Filesystem(new LocalAdapter($this->getTempDir('js_trans_dumper'))),
        ]));


        $this->dumper = new JsTranslationDumper(
            $this->translationController,
            $this->createMock(RouterInterface::class),
            self::TRANSLATION_DOMAINS,
            '',
            $this->languageProvider
        );
        $this->dumper->setLogger($this->logger);
        $this->dumper->setFileManager(self::$fileManager);
    }

    public static function assertFileExists(string $filename, string $message = ''): void
    {
        static::assertTrue(self::$fileManager->hasFile($filename), $message);
    }

    public static function assertStringEqualsFile(
        string $expectedFile,
        string $actualString,
        string $message = ''
    ): void {
        static::assertFileExists($expectedFile, $message);

        $constraint = new IsEqual(self::$fileManager->getFile($expectedFile)->getContent());

        static::assertThat($actualString, $constraint, $message);
    }

    public function testDumpTranslations(): void
    {
        $translationFileName = 'translation/en.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en']);

        $this->translationController->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with(self::TRANSLATION_DOMAINS, 'en')
            ->willReturn($translationFileContent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('<info>[file+]</info> ' . $translationFileName);

        $this->dumper->dumpTranslations();

        self::assertFileExists($translationFileName);
        self::assertStringEqualsFile($translationFileName, $translationFileContent);
    }

    public function testDumpTranslationsFailed()
    {
        $translationFileName = 'translation/en.json';
        $translationFileContent = 'test';

        $exception = new \Exception('Authentication failed.');
        $ioExceptionMessage = sprintf(
            'An error occurred while dumping content to %s, %s',
            $translationFileName,
            $exception->getMessage()
        );

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en']);

        $fileManager = $this->createMock(FileManager::class);

        $fileManager->expects($this->once())
            ->method('writeToStorage')
            ->willThrowException($exception);

        $this->translationController->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with(self::TRANSLATION_DOMAINS, 'en')
            ->willReturn($translationFileContent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('<info>[file+]</info> ' . $translationFileName);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($ioExceptionMessage);

        $this->expectErrorMessage($ioExceptionMessage);
        $this->dumper->setFileManager($fileManager);
        $this->dumper->dumpTranslations();

        $this->dumper->setFileManager(self::$fileManager);
    }

    public function testDumpTranslationsWithLocales(): void
    {
        $translationFileName = 'translation/en_US.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects($this->never())
            ->method('getAvailableLanguageCodes');

        $this->translationController->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with(self::TRANSLATION_DOMAINS, 'en_US')
            ->willReturn($translationFileContent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('<info>[file+]</info> ' . $translationFileName);

        $this->dumper->dumpTranslations(['en_US']);

        self::assertFileExists($translationFileName);
        self::assertStringEqualsFile($translationFileName, $translationFileContent);
    }

    public function testIsTranslationFileExistForExistingFile(): void
    {
        $translationFileName = 'translation/en_GB.json';
        $translationFileContent = 'test';

        self::$fileManager->writeToStorage($translationFileContent, $translationFileName);

        self::assertTrue($this->dumper->isTranslationFileExist('en_GB'));
    }

    public function testIsTranslationFileExistWhenFileDoesNotExist(): void
    {
        self::assertFalse($this->dumper->isTranslationFileExist('en_AU'));
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Gaufrette\Filesystem;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationGenerator;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\Constraint\IsEqual;
use Symfony\Component\Filesystem\Exception\IOException;

class JsTranslationDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var JsTranslationGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private static $fileManager;

    /** @var JsTranslationDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(JsTranslationGenerator::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        self::$fileManager = new FileManager('js_trans_dumper');
        self::$fileManager->setProtocol('gaufrette');
        self::$fileManager->setFilesystemMap(new FilesystemMap([
            'js_trans_dumper' => new Filesystem(new LocalAdapter($this->getTempDir('js_trans_dumper'))),
        ]));

        $this->dumper = new JsTranslationDumper(
            $this->generator,
            $this->languageProvider,
            self::$fileManager
        );
    }

    public static function assertFileExists(string $filename, string $message = ''): void
    {
        self::assertTrue(self::$fileManager->hasFile($filename), $message);
    }

    public static function assertStringEqualsFile(
        string $expectedFile,
        string $actualString,
        string $message = ''
    ): void {
        self::assertFileExists($expectedFile, $message);

        $constraint = new IsEqual(self::$fileManager->getFile($expectedFile)->getContent());

        self::assertThat($actualString, $constraint, $message);
    }

    public function testGetAllLocales(): void
    {
        $locales = ['en', 'en_US'];

        $this->languageProvider->expects(self::once())
            ->method('getAvailableLanguageCodes')
            ->willReturn($locales);

        self::assertEquals($locales, $this->dumper->getAllLocales());
    }

    public function testDumpTranslations(): void
    {
        $translationFileName = 'translation/en.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en']);

        $this->generator->expects($this->once())
            ->method('generateJsTranslations')
            ->with('en')
            ->willReturn($translationFileContent);

        $this->dumper->dumpTranslations();

        self::assertFileExists($translationFileName);
        self::assertStringEqualsFile($translationFileName, $translationFileContent);
    }

    public function testDumpTranslationsWithLocales(): void
    {
        $translationFileName = 'translation/en_US.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects($this->never())
            ->method('getAvailableLanguageCodes');

        $this->generator->expects($this->once())
            ->method('generateJsTranslations')
            ->with('en_US')
            ->willReturn($translationFileContent);

        $this->dumper->dumpTranslations(['en_US']);

        self::assertFileExists($translationFileName);
        self::assertStringEqualsFile($translationFileName, $translationFileContent);
    }

    public function testDumpTranslationFile(): void
    {
        $translationFileName = 'translation/en_US.json';
        $translationFileContent = 'test';

        $this->generator->expects(self::once())
            ->method('generateJsTranslations')
            ->with('en_US')
            ->willReturn($translationFileContent);

        $this->dumper->dumpTranslationFile('en_US');

        self::assertFileExists($translationFileName);
        self::assertStringEqualsFile($translationFileName, $translationFileContent);
    }

    public function testDumpTranslationFileWhenItIsFailed(): void
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

        $this->generator->expects($this->once())
            ->method('generateJsTranslations')
            ->with('en')
            ->willReturn($translationFileContent);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage($ioExceptionMessage);

        $dumper = new JsTranslationDumper(
            $this->generator,
            $this->languageProvider,
            $fileManager
        );
        $dumper->dumpTranslations();
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

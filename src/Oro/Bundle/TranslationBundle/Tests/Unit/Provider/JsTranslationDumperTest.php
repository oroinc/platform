<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationGenerator;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Exception\IOException;

class JsTranslationDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var JsTranslationGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var JsTranslationDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(JsTranslationGenerator::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->dumper = new JsTranslationDumper(
            $this->generator,
            $this->languageProvider,
            $this->fileManager
        );
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
        $translationFile = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects(self::once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en']);

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en.json')
            ->willReturn($translationFile);

        $this->generator->expects(self::once())
            ->method('generateJsTranslations')
            ->with('en')
            ->willReturn($translationFileContent);

        $this->dumper->dumpTranslations();

        self::assertFileExists($translationFile);
        self::assertStringEqualsFile($translationFile, $translationFileContent);
    }

    public function testDumpTranslationsWithLocales(): void
    {
        $translationFile = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en_US.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects(self::never())
            ->method('getAvailableLanguageCodes');

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en_US.json')
            ->willReturn($translationFile);

        $this->generator->expects(self::once())
            ->method('generateJsTranslations')
            ->with('en_US')
            ->willReturn($translationFileContent);

        $this->dumper->dumpTranslations(['en_US']);

        self::assertFileExists($translationFile);
        self::assertStringEqualsFile($translationFile, $translationFileContent);
    }

    public function testDumpTranslationFile(): void
    {
        $translationFile = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en_US.json';
        $translationFileContent = 'test';

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en_US.json')
            ->willReturn($translationFile);

        $this->generator->expects(self::once())
            ->method('generateJsTranslations')
            ->with('en_US')
            ->willReturn($translationFileContent);

        $this->dumper->dumpTranslationFile('en_US');

        self::assertFileExists($translationFile);
        self::assertStringEqualsFile($translationFile, $translationFileContent);
    }

    public function testDumpTranslationFileWhenItIsFailed(): void
    {
        $translationFile = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en_US.json';
        touch($translationFile);
        if (false === chmod($translationFile, 0444)) {
            self::fail('Cannot set the file permissions necessary for this test.');
        }

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en_US.json')
            ->willReturn($translationFile);
        $this->generator->expects(self::once())
            ->method('generateJsTranslations')
            ->with('en_US')
            ->willReturn('test');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('Unable to write file %s.', $translationFile));

        try {
            $this->dumper->dumpTranslationFile('en_US');
        } finally {
            chmod($translationFile, 0744);
            unlink($translationFile);
        }
    }

    public function testIsTranslationFileExistForExistingFile(): void
    {
        $translationFile = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en.json';
        file_put_contents($translationFile, 'test');

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en.json')
            ->willReturn($translationFile);

        self::assertTrue($this->dumper->isTranslationFileExist('en'));
    }

    public function testIsTranslationFileExistWhenFileDoesNotExist(): void
    {
        $translationFile = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en.json';

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en.json')
            ->willReturn($translationFile);

        self::assertFalse($this->dumper->isTranslationFileExist('en'));
    }
}

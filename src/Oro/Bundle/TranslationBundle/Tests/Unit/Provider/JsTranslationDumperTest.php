<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationGenerator;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;

class JsTranslationDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var JsTranslationGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var JsTranslationDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(JsTranslationGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->dumper = new JsTranslationDumper(
            $this->generator,
            $this->languageProvider,
            $this->fileManager
        );
        $this->dumper->setLogger($this->logger);
    }

    public function testDumpTranslations(): void
    {
        $translationFilePath = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en']);

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en.json')
            ->willReturn($translationFilePath);

        $this->generator->expects($this->once())
            ->method('generateJsTranslations')
            ->with('en')
            ->willReturn($translationFileContent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('<info>[file+]</info> ' . $translationFilePath);

        $this->dumper->dumpTranslations();

        self::assertFileExists($translationFilePath);
        self::assertStringEqualsFile($translationFilePath, $translationFileContent);
    }

    public function testDumpTranslationsWithLocales(): void
    {
        $translationFilePath = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en_US.json';
        $translationFileContent = 'test';

        $this->languageProvider->expects($this->never())
            ->method('getAvailableLanguageCodes');

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en_US.json')
            ->willReturn($translationFilePath);

        $this->generator->expects($this->once())
            ->method('generateJsTranslations')
            ->with('en_US')
            ->willReturn($translationFileContent);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('<info>[file+]</info> ' . $translationFilePath);

        $this->dumper->dumpTranslations(['en_US']);

        self::assertFileExists($translationFilePath);
        self::assertStringEqualsFile($translationFilePath, $translationFileContent);
    }

    public function testIsTranslationFileExistForExistingFile(): void
    {
        $translationFilePath = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en.json';
        file_put_contents($translationFilePath, 'test');

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en.json')
            ->willReturn($translationFilePath);

        self::assertTrue($this->dumper->isTranslationFileExist('en'));
    }

    public function testIsTranslationFileExistWhenFileDoesNotExist(): void
    {
        $translationFilePath = $this->getTempDir('js_trans_dumper') . DIRECTORY_SEPARATOR . 'en.json';

        $this->fileManager->expects(self::once())
            ->method('getFilePath')
            ->with('translation/en.json')
            ->willReturn($translationFilePath);

        self::assertFalse($this->dumper->isTranslationFileExist('en'));
    }
}

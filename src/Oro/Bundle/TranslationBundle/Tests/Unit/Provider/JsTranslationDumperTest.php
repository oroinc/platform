<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TranslationBundle\Controller\Controller;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;
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
    private $fileManager;

    /** @var JsTranslationDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->translationController = $this->createMock(Controller::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->dumper = new JsTranslationDumper(
            $this->translationController,
            $this->createMock(RouterInterface::class),
            self::TRANSLATION_DOMAINS,
            '',
            $this->languageProvider
        );
        $this->dumper->setLogger($this->logger);
        $this->dumper->setFileManager($this->fileManager);
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

        $this->translationController->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with(self::TRANSLATION_DOMAINS, 'en')
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

        $this->translationController->expects($this->once())
            ->method('renderJsTranslationContent')
            ->with(self::TRANSLATION_DOMAINS, 'en_US')
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

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\TempDirExtension;

class OroTranslationDumpCommandTest extends WebTestCase
{
    use TempDirExtension;

    private const COMMAND_NAME = 'oro:translation:dump';
    private const GAUFRETTE_BASE_PATH = 'gaufrette://public_js/js/translation/';

    private string $tempDir;

    protected function setUp(): void
    {
        $this->initClient();
        $this->tempDir = $this->getTempDir('translation_dump_command');
    }

    private function doTest(array $targetFilePaths, array $locales): void
    {
        $backupFilePaths = [];
        foreach ($targetFilePaths as $k => $targetFilePath) {
            if (file_exists($targetFilePath)) {
                $backupFilePath = $this->tempDir . DIRECTORY_SEPARATOR . sprintf('trans_%s.bkp', $k);
                $backupFilePaths[$targetFilePath] = $backupFilePath;
                $this->moveFile($targetFilePath, $backupFilePath);
            }
        }

        try {
            $result = $this->runCommand(self::COMMAND_NAME, $locales, true, true);
            foreach ($targetFilePaths as $targetFilePath) {
                self::assertStringContainsString($targetFilePath, $result);
                self::assertFileExists($targetFilePath);
            }
        } finally {
            foreach ($targetFilePaths as $targetFilePath) {
                if (file_exists($targetFilePath)) {
                    unlink($targetFilePath);
                }
            }
            foreach ($backupFilePaths as $targetFilePath => $backupFilePath) {
                $this->moveFile($backupFilePath, $targetFilePath);
            }
        }
    }

    private function moveFile(string $from, string $to): void
    {
        // the rename() function cannot be used across stream wrappers
        file_put_contents($to, file_get_contents($from));
        unlink($from);
    }

    public function testExecuteForOneLocale(): void
    {
        $this->doTest(
            [
                self::GAUFRETTE_BASE_PATH . 'en.json'
            ],
            ['en']
        );
    }

    public function testExecuteForSeveralLocales(): void
    {
        $this->doTest(
            [
                self::GAUFRETTE_BASE_PATH . 'en.json',
                self::GAUFRETTE_BASE_PATH . 'en_US.json'
            ],
            ['en', 'en_US']
        );
    }

    public function testExecuteForAllLocales(): void
    {
        $targetFilePaths = [];
        /** @var LanguageProvider $languageProvider */
        $languageProvider = self::getContainer()->get('oro_translation.provider.language');
        $locales = $languageProvider->getAvailableLanguageCodes();
        foreach ($locales as $locale) {
            $targetFilePaths[] = sprintf(self::GAUFRETTE_BASE_PATH . '%s.json', $locale);
        }

        $this->doTest($targetFilePaths, []);
    }
}

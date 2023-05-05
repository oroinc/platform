<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Command\DumpTranslationToFilesCommand;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Download\TranslationServiceAdapterStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReader;

class DumpTranslationToFilesCommandTest extends WebTestCase
{
    use TempDirExtension;

    private const COMMAND_NAME = 'oro:translation:dump-files';

    private string $tempDir;
    private TranslationReader $translationReader;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTranslations::class]);
        $this->tempDir = $this->getTempDir('dump_translation_command');
        $this->translationReader = self::getContainer()->get('translation.reader');

        // change the translationServiceAdapter at the command instance to the stub
        // to be able to work with local archive instead of real calls to the remote service.
        $archivePath = realpath(__DIR__ . '/../Stub/translations.zip');
        $translationServiceAdapter = new TranslationServiceAdapterStub($archivePath);
        $loader = self::getContainer()->get('oro_translation.catalogue_loader.crowdin');
        ReflectionUtil::setPropertyValue($loader, 'translationServiceAdapter', $translationServiceAdapter);

        // change the directory of the dumped files to the temp directory
        // because default translation path from the translator.default_path parameter can be
        // forbidden to write at the test instances.
        $command = self::getContainer()->get(DumpTranslationToFilesCommand::class);
        ReflectionUtil::setPropertyValue($command, 'targetPath', $this->tempDir);
    }

    private function loadTranslationsFromFiles(string $locale): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $this->translationReader->read($this->tempDir, $catalogue);

        return $catalogue;
    }

    public function testDumpFromDatabase(): void
    {
        self::runCommand(self::COMMAND_NAME, ['--locale' => 'fr_FR'], true, true);
        $dumpedCatalogue = $this->loadTranslationsFromFiles('fr_FR');
        self::assertEquals(['test_domain'], $dumpedCatalogue->getDomains());
        self::assertEquals(
            [
                'translation.trans3' => 'translation.trans3',
                'translation.trans4' => 'translation.trans4',
                'translation.trans5' => 'translation.trans5'
            ],
            $dumpedCatalogue->all('test_domain')
        );
    }

    public function testDumpFromDatabaseWithExistingTranslationInAnotherFormat(): void
    {
        $this->copyToTempDir('dump_translation_command', realpath(__DIR__ . '/../Stub/translations'));
        $result = self::runCommand(self::COMMAND_NAME, ['--locale' => 'fr_FR']);

        self::assertStringContainsString(
            'The translation directory have the dumped files for the \'fr_FR\' locale not in \'yml\' format',
            $result
        );
    }

    public function testDumpFromCrowdin(): void
    {
        self::runCommand(self::COMMAND_NAME, ['--locale' => 'en', '--source' => 'crowdin'], true, true);
        $dumpedCatalogue = $this->loadTranslationsFromFiles('en');
        $domains = $dumpedCatalogue->getDomains();
        self::assertCount(4, $domains);
        self::assertContains('config', $domains);
        self::assertContains('validators', $domains);
        self::assertContains('messages', $domains);
        self::assertContains('jsmessages', $domains);
        self::assertEquals('Invalid credentials.', $dumpedCatalogue->get('Invalid credentials.', 'security'));
        self::assertEquals('Delete', $dumpedCatalogue->get('Delete'));
        self::assertEquals('User', $dumpedCatalogue->get('entity.user.name', 'config'));
    }

    public function testDumpOnlyNewItems(): void
    {
        $this->copyToTempDir('dump_translation_command', realpath(__DIR__ . '/../Stub/translations/existing'));
        $existingCatalogue = $this->loadTranslationsFromFiles('en');

        $output = self::runCommand(
            self::COMMAND_NAME,
            ['--locale' => 'en','--source' => 'crowdin', '--new-only' => true],
            true,
            true
        );
        $updatedCatalogue = $this->loadTranslationsFromFiles('en');
        $domains = $updatedCatalogue->getDomains();

        self::assertContains('config', $domains);
        self::assertContains('validators', $domains);
        self::assertContains('messages', $domains);
        self::assertContains('jsmessages', $domains);
        self::assertCount(1, $existingCatalogue->getDomains());

        // if the dumped files have another value for translation key, this value should not be changed
        // but new value should be dumped to console output.
        self::assertStringContainsString('config entity.user.name User_changed User', $output);
        self::assertEquals('User_changed', $existingCatalogue->get('entity.user.name', 'config'));
        self::assertEquals('User_changed', $updatedCatalogue->get('entity.user.name', 'config'));

        // check the new key
        self::assertFalse($existingCatalogue->has('entity.role.name', 'config'));
        self::assertEquals('Role', $updatedCatalogue->get('entity.role.name', 'config'));
    }
}

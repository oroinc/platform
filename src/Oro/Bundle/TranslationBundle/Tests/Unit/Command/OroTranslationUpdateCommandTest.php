<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Command\OroTranslationUpdateCommand;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Intl\Locales;

class OroTranslationUpdateCommandTest extends \PHPUnit\Framework\TestCase
{
    use CommandTestingTrait;
    use TempDirExtension;

    private TranslationDownloader $translationDownloader;
    private LoggerInterface $logger;
    private LanguageRepository $languageRepository;
    private OroTranslationUpdateCommand $command;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->translationDownloader = $this->createMock(TranslationDownloader::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->languageRepository = $this->createMock(LanguageRepository::class);
        $doctrine->method('getRepository')->willReturnMap([
            [Language::class, null, $this->languageRepository]
        ]);
        $doctrine->method('getManagerForClass')->willReturnMap([
            [Language::class, $this->createMock(EntityManager::class)]
        ]);

        $this->command = new OroTranslationUpdateCommand(
            $this->translationDownloader,
            $doctrine,
            $this->logger
        );
    }

    public function testExecuteWithoutOptions(): void
    {
        $this->languageRepository->method('findAll')->willReturn([
            (new Language())->setCode('en_US'),
            (new Language())->setCode('uk_UA'),
        ]);

        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertOutputContains($commandTester, 'Available Translations');
        $this->assertOutputContains($commandTester, 'en_US');
        $this->assertOutputContains($commandTester, 'uk_UA');
    }

    public function testExecute(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->method('findOneBy')->willReturnMap([
            [['code' => $langCode], null, (new Language())->setCode($langCode)]
        ]);

        $this->translationDownloader->method('fetchLanguageMetrics')->willReturnMap([
            [$langCode, ['code' => $langCode, 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]]
        ]);

        $this->translationDownloader->expects(static::once())
            ->method('downloadTranslationsArchive')
            ->with($langCode, static::isType('string'));

        $this->translationDownloader->expects(static::once())
            ->method('loadTranslationsFromArchive')
            ->with(static::isType('string'), $langCode);

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        $langName = Locales::getName($langCode, 'en');
        $this->assertOutputContains($commandTester, \sprintf('%s (%s):', $langName, $langCode));
        $this->assertOutputContains($commandTester, 'Checking availability...');
        $this->assertOutputContains($commandTester, 'Downloading translations...');
        $this->assertOutputContains($commandTester, 'Applying translations...');
        $this->assertOutputContains($commandTester, \sprintf('Update completed for "%s" language.', $langName));
    }

    public function testForLanguageWithoutTranslations(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->method('findOneBy')->willReturnMap([
            [['code' => $langCode], null, (new Language())->setCode($langCode)]
        ]);

        $this->translationDownloader->method('fetchLanguageMetrics')->willReturn(null);

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        $langName = Locales::getName($langCode, 'en');
        $this->assertOutputContains($commandTester, \sprintf('%s (%s):', $langName, $langCode));
        $this->assertOutputContains($commandTester, 'Checking availability...');
        $this->assertProducedError(
            $commandTester,
            \sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode)
        );
    }

    public function testWithNotInstalledLanguage(): void
    {
        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                'Language "{language}" is not installed.'
                . ' Translations can be updated only for an already installed language.',
                static::callback(static fn ($context) => 'WR_ONG' === $context['language'])
            );

        $commandTester = $this->doExecuteCommand($this->command, ['language' => 'WR_ONG']);

        $this->assertProducedError(
            $commandTester,
            'Language "WR_ONG" is not installed. Translations can be updated only for an already installed language.',
        );
    }

    public function testWithAllOption(): void
    {
        $langCodes = ['de_DE', 'fr_FR', 'uk_UA'];
        $languages = [
            (new Language())->setCode($langCodes[0]),
            (new Language())->setCode($langCodes[1]),
            (new Language())->setCode($langCodes[2]),
        ];

        $this->languageRepository->method('findAll')->willReturn($languages);
        $this->languageRepository->method('findOneBy')->willReturnMap([
            [['code' => $langCodes[0]], null, $languages[0]],
            [['code' => $langCodes[1]], null, $languages[1]],
            [['code' => $langCodes[2]], null, $languages[2]],
        ]);

        $this->translationDownloader->method('fetchLanguageMetrics')->willReturnMap([
            [$langCodes[0], ['code' => $langCodes[0], 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]],
            [$langCodes[1], null], // no translations available
            [$langCodes[2], ['code' => $langCodes[1], 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]],
        ]);

        $this->translationDownloader->expects(static::exactly(2))
            ->method('downloadTranslationsArchive')
            ->withConsecutive(
                [$langCodes[0], static::isType('string')],
                // skipping 1 because no available translations
                [$langCodes[2], static::isType('string')]
            );

        $this->translationDownloader->expects(static::exactly(2))
            ->method('loadTranslationsFromArchive')
            ->withConsecutive(
                [static::isType('string'), $langCodes[0]],
                // skipping 1 because no available translations
                [static::isType('string'), $langCodes[2]],
            );

        $commandTester = $this->doExecuteCommand($this->command, ['--all' => true]);

        foreach ($langCodes as $index => $langCode) {
            $langName = Locales::getName($langCode, 'en');
            $this->assertOutputContains($commandTester, \sprintf('%s (%s):', $langName, $langCode));
            $this->assertOutputContains($commandTester, 'Checking availability...');
            if (1 === $index) {
                $this->assertOutputContains(
                    $commandTester,
                    \sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode)
                );
            } else {
                $this->assertOutputContains($commandTester, 'Downloading translations...');
                $this->assertOutputContains($commandTester, 'Applying translations...');
                $this->assertOutputContains($commandTester, \sprintf('Update completed for "%s" language.', $langName));
            }
        }
    }

    public function testWithAllOptionAndLanguageArgument(): void
    {
        $language = 'fr_FR';

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                'The --all option and the language argument ("{language}") cannot be used together.',
                static::callback(static fn ($context) => $context['language'] === $language)
            );

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $language, '--all' => true]);

        $this->assertProducedError(
            $commandTester,
            \sprintf('The --all option and the language argument ("%s") cannot be used together.', $language)
        );
    }
}

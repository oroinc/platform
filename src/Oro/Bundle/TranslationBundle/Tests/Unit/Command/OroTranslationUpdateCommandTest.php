<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
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

    /** @var TranslationDownloader|\PHPUnit\Framework\MockObject\MockObject */
    private $translationDownloader;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $languageRepository;

    /** @var OroTranslationUpdateCommand */
    private $command;

    protected function setUp(): void
    {
        $this->translationDownloader = $this->createMock(TranslationDownloader::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->languageRepository = $this->createMock(LanguageRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($this->languageRepository);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($this->createMock(EntityManagerInterface::class));

        $this->command = new OroTranslationUpdateCommand(
            $this->translationDownloader,
            $doctrine,
            $this->logger
        );
    }

    private function getLanguage(string $code): Language
    {
        $language = new Language();
        $language->setCode($code);

        return $language;
    }

    public function testExecuteWithoutOptions(): void
    {
        $this->languageRepository->expects(self::once())
            ->method('findAll')
            ->willReturn([
                $this->getLanguage('en_US'),
                $this->getLanguage('uk_UA'),
                $this->getLanguage('en_plastimo')
            ]);

        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertOutputContains($commandTester, 'Available Translations');
        $this->assertOutputContains($commandTester, 'en_US');
        $this->assertOutputContains($commandTester, 'uk_UA');
    }

    public function testExecute(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $langCode])
            ->willReturn((new Language())->setCode($langCode));

        $this->translationDownloader->expects(self::once())
            ->method('fetchLanguageMetrics')
            ->with($langCode)
            ->willReturn(['code' => $langCode, 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]);

        $this->translationDownloader->expects(self::once())
            ->method('downloadTranslationsArchive')
            ->with($langCode, self::isType('string'));

        $this->translationDownloader->expects(self::once())
            ->method('loadTranslationsFromArchive')
            ->with(self::isType('string'), $langCode);

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        $langName = Locales::getName($langCode, 'en');
        $this->assertOutputContains($commandTester, sprintf('%s (%s):', $langName, $langCode));
        $this->assertOutputContains($commandTester, 'Checking availability...');
        $this->assertOutputContains($commandTester, 'Downloading translations...');
        $this->assertOutputContains($commandTester, 'Applying translations...');
        $this->assertOutputContains($commandTester, sprintf('Update completed for "%s" language.', $langName));
    }

    public function testForLanguageWithoutTranslations(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $langCode])
            ->willReturn((new Language())->setCode($langCode));

        $this->translationDownloader->expects(self::once())
            ->method('fetchLanguageMetrics')
            ->willReturn(null);

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        $langName = Locales::getName($langCode, 'en');
        $this->assertOutputContains($commandTester, sprintf('%s (%s):', $langName, $langCode));
        $this->assertOutputContains($commandTester, 'Checking availability...');
        $this->assertProducedError(
            $commandTester,
            sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode)
        );
    }

    public function testWithNotInstalledLanguage(): void
    {
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Language "{language}" is not installed.'
                . ' Translations can be updated only for an already installed language.',
                self::callback(static fn ($context) => 'WR_ONG' === $context['language'])
            );

        $commandTester = $this->doExecuteCommand($this->command, ['language' => 'WR_ONG']);

        $this->assertProducedError(
            $commandTester,
            'Language "WR_ONG" is not installed. Translations can be updated only for an already installed language.',
        );
    }

    public function testWithAllOption(): void
    {
        $codes = ['de_DE', 'fr_FR', 'uk_UA', 'en_plastimo'];

        $this->languageRepository->expects(self::once())
            ->method('findAll')
            ->willReturn(array_map(
                function (string $code): Language {
                    return $this->getLanguage($code);
                },
                $codes
            ));
        $this->languageRepository->expects(self::never())
            ->method('findOneBy');

        $this->translationDownloader->expects(self::exactly(4))
            ->method('fetchLanguageMetrics')
            ->willReturnMap([
                [$codes[0], ['code' => $codes[0], 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]],
                [$codes[1], null], // no translations available
                [$codes[2], ['code' => $codes[1], 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]],
                [$codes[3], null],
            ]);

        $this->translationDownloader->expects(self::exactly(2))
            ->method('downloadTranslationsArchive')
            ->withConsecutive(
                [$codes[0], self::isType('string')],
                // skipping 1 because no available translations
                [$codes[2], self::isType('string')]
            );

        $this->translationDownloader->expects(self::exactly(2))
            ->method('loadTranslationsFromArchive')
            ->withConsecutive(
                [self::isType('string'), $codes[0]],
                // skipping 1 because no available translations
                [self::isType('string'), $codes[2]],
            );

        $commandTester = $this->doExecuteCommand($this->command, ['--all' => true]);

        $nonAvailableTranslations = [1, 3];
        foreach ($codes as $index => $langCode) {
            $langName = Locales::exists($langCode) ? Locales::getName($langCode, 'en') : $langCode;
            $this->assertOutputContains($commandTester, sprintf('%s (%s):', $langName, $langCode));
            $this->assertOutputContains($commandTester, 'Checking availability...');
            if (\in_array($index, $nonAvailableTranslations, true)) {
                $this->assertOutputContains(
                    $commandTester,
                    sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode)
                );
            } else {
                $this->assertOutputContains($commandTester, 'Downloading translations...');
                $this->assertOutputContains($commandTester, 'Applying translations...');
                $this->assertOutputContains($commandTester, sprintf('Update completed for "%s" language.', $langName));
            }
        }
    }

    public function testWithAllOptionAndLanguageArgument(): void
    {
        $language = 'fr_FR';

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The --all option and the language argument ("{language}") cannot be used together.',
                self::callback(static fn ($context) => $context['language'] === $language)
            );

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $language, '--all' => true]);

        $this->assertProducedError(
            $commandTester,
            sprintf('The --all option and the language argument ("%s") cannot be used together.', $language)
        );
    }
}

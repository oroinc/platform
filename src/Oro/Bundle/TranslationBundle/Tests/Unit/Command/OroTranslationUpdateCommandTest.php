<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Command\OroTranslationUpdateCommand;
use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Helper\FileBasedLanguageHelper;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Intl\Locales;

class OroTranslationUpdateCommandTest extends \PHPUnit\Framework\TestCase
{
    use CommandTestingTrait;
    use TempDirExtension;

    /** @var TranslationDownloader|\PHPUnit\Framework\MockObject\MockObject */
    private $translationDownloader;

    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $languageRepository;

    /** @var FileBasedLanguageHelper|\PHPUnit\Framework\MockObject\MockObject  */
    private $fileBasedLanguageHelper;

    /** @var OroTranslationUpdateCommand */
    private $command;

    protected function setUp(): void
    {
        $this->translationDownloader = $this->createMock(TranslationDownloader::class);
        $this->languageRepository = $this->createMock(LanguageRepository::class);
        $this->fileBasedLanguageHelper = $this->createMock(FileBasedLanguageHelper::class);

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
            $this->fileBasedLanguageHelper
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
        $this->fileBasedLanguageHelper->expects(self::never())
            ->method('isFileBasedLocale');

        $commandTester = $this->doExecuteCommand($this->command);

        self::assertSame(0, $commandTester->getStatusCode());
        $this->assertOutputContains($commandTester, 'Available Translations');
        $this->assertOutputContains($commandTester, 'en_US');
        $this->assertOutputContains($commandTester, 'uk_UA');
    }

    public function testExecuteForOneLanguage(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $langCode])
            ->willReturn($this->getLanguage($langCode));

        $this->translationDownloader->expects(self::once())
            ->method('fetchLanguageMetrics')
            ->with($langCode)
            ->willReturn(['code' => $langCode, 'translationStatus' => 99, 'lastBuildDate' => new \DateTime()]);

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with($langCode)
            ->willReturn(false);

        $this->translationDownloader->expects(self::once())
            ->method('downloadTranslationsArchive')
            ->with($langCode, self::isType('string'));

        $this->translationDownloader->expects(self::once())
            ->method('loadTranslationsFromArchive')
            ->with(self::isType('string'), $langCode);

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        self::assertSame(0, $commandTester->getStatusCode());
        $langName = Locales::getName($langCode, 'en');
        $this->assertOutputContains($commandTester, sprintf('%s (%s):', $langName, $langCode));
        $this->assertOutputContains($commandTester, 'Checking availability...');
        $this->assertOutputContains($commandTester, 'Downloading translations...');
        $this->assertOutputContains($commandTester, 'Applying translations...');
        $this->assertOutputContains($commandTester, sprintf('Update completed for "%s" language.', $langName));
    }

    public function testExecuteForLanguageWithoutTranslations(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $langCode])
            ->willReturn($this->getLanguage($langCode));

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with($langCode)
            ->willReturn(false);

        $this->translationDownloader->expects(self::once())
            ->method('fetchLanguageMetrics')
            ->willReturn(null);

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        self::assertSame(1, $commandTester->getStatusCode());
        $langName = Locales::getName($langCode, 'en');
        $this->assertOutputContains($commandTester, sprintf('%s (%s):', $langName, $langCode));
        $this->assertOutputContains($commandTester, 'Checking availability...');
        $this->assertProducedError(
            $commandTester,
            sprintf('No "%s" (%s) translations are available for download.', $langName, $langCode)
        );
    }

    public function testExecuteWithNotInstalledLanguage(): void
    {
        $commandTester = $this->doExecuteCommand($this->command, ['language' => 'WR_ONG']);

        self::assertSame(1, $commandTester->getStatusCode());
        $this->assertProducedError(
            $commandTester,
            'Language "WR_ONG" is not installed. Translations can be updated only for an already installed language.',
        );
    }

    public function testExecuteWithAllOption(): void
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

        $this->fileBasedLanguageHelper->expects(self::exactly(4))
            ->method('isFileBasedLocale')
            ->willReturnMap([
                [$codes[0], false],
                [$codes[1], false],
                [$codes[2], false],
                [$codes[3], false],
            ]);

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

        self::assertSame(0, $commandTester->getStatusCode());
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

    public function testExecuteWithAllOptionAndLanguageArgument(): void
    {
        $commandTester = $this->doExecuteCommand($this->command, ['language' => 'fr_FR', '--all' => true]);

        self::assertSame(1, $commandTester->getStatusCode());
        $this->assertProducedError(
            $commandTester,
            'The --all option and the language argument ("fr_FR") cannot be used together.'
        );
    }

    public function testExecuteForOneFilesBasedLanguage(): void
    {
        $langCode = 'fr_FR';
        $this->languageRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $langCode])
            ->willReturn($this->getLanguage($langCode));

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with($langCode)
            ->willReturn(true);

        $this->translationDownloader->expects(self::never())
            ->method('fetchLanguageMetrics');

        $this->translationDownloader->expects(self::never())
            ->method('downloadTranslationsArchive');

        $this->translationDownloader->expects(self::never())
            ->method('loadTranslationsFromArchive');

        $commandTester = $this->doExecuteCommand($this->command, ['language' => $langCode]);

        self::assertSame(0, $commandTester->getStatusCode());
        $this->assertOutputContains($commandTester, 'Language "fr_FR" is file based.');
    }

    public function testExecuteWithAllOptionWithFilesBasedLanguage(): void
    {
        $codes = ['uk_UA'];

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

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with('uk_UA')
            ->willReturn(true);

        $this->translationDownloader->expects(self::never())
            ->method('fetchLanguageMetrics');

        $this->translationDownloader->expects(self::never())
            ->method('downloadTranslationsArchive');

        $this->translationDownloader->expects(self::never())
            ->method('loadTranslationsFromArchive');

        $commandTester = $this->doExecuteCommand($this->command, ['--all' => true]);

        self::assertSame(0, $commandTester->getStatusCode());
        $this->assertOutputContains($commandTester, 'Language "uk_UA" is file based.');
    }
}

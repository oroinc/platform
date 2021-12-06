<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Command;

use Oro\Bundle\LocaleBundle\Command\UpdateLocalizationCommand;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;

/**
 * @dbIsolationPerTest
 */
class UpdateLocalizationCommandTest extends WebTestCase
{
    private LocalizationRepository $localizationRepository;
    private LanguageRepository $languageRepository;

    protected function setUp(): void
    {
        $this->initClient();

        $registry = $this->getContainer()->get('doctrine');

        $this->localizationRepository = $registry->getManagerForClass(Localization::class)
            ->getRepository(Localization::class);

        $this->languageRepository = $registry->getManagerForClass(Language::class)
            ->getRepository(Language::class);
    }

    public function testRun(): void
    {
        // Assert default localization is present
        $this->assertLocalizationExists('en', 'en_US', 'English (United States)');
        $this->assertEnglishLanguageExists(); // And English language is not present

        $this->runCommand(
            UpdateLocalizationCommand::getDefaultName(),
            [
                '--' . 'formatting-code' => 'de_DE',
                '--' . 'language' => 'de'
            ]
        );

        // Check that only one localization is present and it is de_DE
        $this->assertLocalizationExists('de', 'de_DE', 'Deutsch (Deutschland)');
        $this->assertLocalizationNotExists('en'); // And English localization is not present
        $this->assertEnglishLanguageExists(); // And English language is present

        // Run command again for in order to make sure that it does not affect non default localization
        $this->runCommand(
            UpdateLocalizationCommand::getDefaultName(),
            [
                '--' . 'formatting-code' => 'it_IT',
                '--' . 'language' => 'it'
            ]
        );

        // Check that only one localization is present and it is still de_DE
        $this->assertLocalizationExists('de', 'de_DE', 'Deutsch (Deutschland)');
        $this->assertLocalizationNotExists('it_IT'); // And Italian localization is not present
        $this->assertLanguageNotExists('it'); // And Italian language is not present too
        $this->assertEnglishLanguageExists(); // And English language is present
    }

    private function assertLocalizationExists(string $languageCode, string $formattingCode, string $name): void
    {
        $localizations = $this->localizationRepository->findAll();
        self::assertCount(1, $localizations);
        /** @var Localization $localization */
        $localization = reset($localizations);

        self::assertEquals($languageCode, $localization->getLanguageCode());
        self::assertEquals($formattingCode, $localization->getFormattingCode());
        self::assertEquals($name, $localization->getName());
        self::assertEquals($name, $localization->getTitle());
    }

    private function assertEnglishLanguageExists(): void
    {
        self::assertFalse(null === $this->languageRepository->findOneBy(['code' => 'en']));
    }

    private function assertLocalizationNotExists(string $formattingCode): void
    {
        self::assertTrue(null === $this->localizationRepository->findOneBy(['formattingCode' => $formattingCode]));
    }

    private function assertLanguageNotExists(string $code): void
    {
        self::assertTrue(null === $this->languageRepository->findOneBy(['code' => $code]));
    }
}

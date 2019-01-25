<?php

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
    /** @var LocalizationRepository */
    private $localizationRepository;

    /** @var LanguageRepository */
    private $languageRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
        $this->assertLocalizationExists('en', 'en', 'English');
        $this->assertEnglishLanguageExists(); // And English language is not present

        $this->runCommand(
            UpdateLocalizationCommand::NAME,
            [
                '--' . UpdateLocalizationCommand::OPTION_FORMATTING_CODE => 'de_DE',
                '--' . UpdateLocalizationCommand::OPTION_LANGUAGE => 'de'
            ]
        );

        // Check that only one localization is present and it is de_DE
        $this->assertLocalizationExists('de', 'de_DE', 'Deutsch (Deutschland)');
        $this->assertLocalizationNotExists('en'); // And English localization is not present
        $this->assertEnglishLanguageExists(); // And English language is present

        // Run command again for in order to make sure that it does not affect non default localization
        $this->runCommand(
            UpdateLocalizationCommand::NAME,
            [
                '--' . UpdateLocalizationCommand::OPTION_FORMATTING_CODE => 'it_IT',
                '--' . UpdateLocalizationCommand::OPTION_LANGUAGE => 'it'
            ]
        );

        // Check that only one localization is present and it is still de_DE
        $this->assertLocalizationExists('de', 'de_DE', 'Deutsch (Deutschland)');
        $this->assertLocalizationNotExists('it_IT'); // And Italian localization is not present
        $this->assertLanguageNotExists('it'); // And Italian language is not present too
        $this->assertEnglishLanguageExists(); // And English language is present
    }

    /**
     * @param string $languageCode
     * @param string $formattingCode
     * @param string $name
     */
    private function assertLocalizationExists(string $languageCode, string $formattingCode, string $name): void
    {
        $localizations = $this->localizationRepository->findAll();
        $this->assertCount(1, $localizations);
        /** @var Localization $localization */
        $localization = reset($localizations);

        $this->assertEquals($languageCode, $localization->getLanguageCode());
        $this->assertEquals($formattingCode, $localization->getFormattingCode());
        $this->assertEquals($name, $localization->getName());
        $this->assertEquals($name, $localization->getTitle());
    }

    private function assertEnglishLanguageExists(): void
    {
        $this->assertFalse(null === $this->languageRepository->findOneBy(['code' => 'en']));
    }

    /**
     * @param string $formattingCode
     */
    private function assertLocalizationNotExists(string $formattingCode): void
    {
        $this->assertTrue(null === $this->localizationRepository->findOneBy(['formattingCode' => $formattingCode]));
    }

    /**
     * @param string $code
     */
    private function assertLanguageNotExists(string $code): void
    {
        $this->assertTrue(null === $this->languageRepository->findOneBy(['code' => $code]));
    }
}

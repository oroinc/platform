<?php

declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Command;

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
    private const COMMAND_NAME = 'oro:localization:update';

    private const EN_LANGUAGE_CODE = 'en';
    private const EN_FORMATTING_CODE = 'en_US';
    private const EN_LOCALIZATION_NAME = 'English (United States)';

    private const DE_LANGUAGE_CODE = 'de';
    private const DE_FORMATTING_CODE = 'de_DE';
    private const DE_LOCALIZATION_NAME = 'Deutsch (Deutschland)';

    private const FR_LANGUAGE_CODE = 'fr';
    private const FR_FORMATTING_CODE = 'fr_FR';
    private const FR_LOCALIZATION_NAME = 'français (France)';

    private LocalizationRepository $localizationRepository;
    private LanguageRepository $languageRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $registry = $this->getContainer()
            ->get('doctrine');

        $this->localizationRepository = $registry->getManagerForClass(Localization::class)
            ->getRepository(Localization::class);

        $this->languageRepository = $registry->getManagerForClass(Language::class)
            ->getRepository(Language::class);
    }

    public function testRun(): void
    {
        $this->runUpdateLocalization(self::DE_FORMATTING_CODE, self::DE_LANGUAGE_CODE);

        $this->assertSingleLocalizationExists(
            self::DE_LANGUAGE_CODE,
            self::DE_FORMATTING_CODE,
            self::DE_LOCALIZATION_NAME
        );
        $this->assertLocalizationNotExists(self::EN_FORMATTING_CODE);
        $this->assertLanguageExists(self::EN_LANGUAGE_CODE);
        $this->assertLanguageExists(self::DE_LANGUAGE_CODE);
    }

    public function testRevertToDefaultLocale(): void
    {
        $this->runUpdateLocalization(self::FR_FORMATTING_CODE, self::FR_LANGUAGE_CODE);

        $this->assertSingleLocalizationExists(
            self::FR_LANGUAGE_CODE,
            self::FR_FORMATTING_CODE,
            self::FR_LOCALIZATION_NAME
        );

        $this->runUpdateLocalization(self::EN_FORMATTING_CODE, self::EN_LANGUAGE_CODE);

        $this->assertSingleLocalizationExists(
            self::EN_LANGUAGE_CODE,
            self::EN_FORMATTING_CODE,
            self::EN_LOCALIZATION_NAME
        );
    }

    public function testRunWithExistingLanguage(): void
    {
        $this->createDeutschLanguage();

        $this->runUpdateLocalization(self::DE_FORMATTING_CODE, self::DE_LANGUAGE_CODE);

        $this->assertSingleLocalizationExists(
            self::DE_LANGUAGE_CODE,
            self::DE_FORMATTING_CODE,
            self::DE_LOCALIZATION_NAME
        );
        $this->assertLocalizationNotExists(self::EN_FORMATTING_CODE);
        $this->assertLanguageExists(self::EN_LANGUAGE_CODE);

        // Verify no duplicate language entity was created
        self::assertCount(
            1,
            $this->languageRepository->findBy(['code' => self::DE_LANGUAGE_CODE])
        );
    }

    private function runUpdateLocalization(string $formattingCode, string $languageCode): void
    {
        $params = ['--formatting-code' => $formattingCode, '--language' => $languageCode];

        $this->runCommand(self::COMMAND_NAME, $params);
    }

    private function createDeutschLanguage(): void
    {
        $manager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Language::class);

        $language = new Language();
        $language->setCode(self::DE_LANGUAGE_CODE)
            ->setEnabled(true);

        $manager->persist($language);
        $manager->flush($language);
    }

    private function assertSingleLocalizationExists(string $languageCode, string $formattingCode, string $name): void
    {
        $localizations = $this->localizationRepository->findAll();
        self::assertCount(1, $localizations);

        $localization = reset($localizations);

        self::assertEquals($languageCode, $localization->getLanguageCode());
        self::assertEquals($formattingCode, $localization->getFormattingCode());
        self::assertEquals($name, $localization->getName());
        self::assertEquals($name, $localization->getTitle());
    }

    private function assertLocalizationNotExists(string $formattingCode): void
    {
        self::assertNull($this->localizationRepository->findOneBy(['formattingCode' => $formattingCode]));
    }

    private function assertLanguageExists(string $code): void
    {
        self::assertNotNull($this->languageRepository->findOneBy(['code' => $code]));
    }
}

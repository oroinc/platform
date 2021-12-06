<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Connection;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadLocalizationData::class]);
    }

    private function getRepository(): LocalizationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Localization::class);
    }

    private function getConnection(): Connection
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Localization::class)->getConnection();
    }

    public function testFindRootsWithChildren()
    {
        $localizations = [
            $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE),
            $this->getReference('es')
        ];
        $queryAnalyzer = new QueryAnalyzer($this->getConnection()->getDatabasePlatform());

        $prevLogger = $this->getConnection()->getConfiguration()->getSQLLogger();
        $this->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        /** @var Localization[] $result */
        $result = $this->getRepository()->findRootsWithChildren();

        $this->assertEquals(array_values($localizations), array_values($result));

        foreach ($result as $root) {
            $this->visitChildren($root);
        }

        $queries = $queryAnalyzer->getExecutedQueries();

        $this->assertCount(count($localizations) + 2, $queries);

        $this->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    private function visitChildren(Localization $localization)
    {
        $localization->getLanguageCode();
        foreach ($localization->getChildLocalizations() as $child) {
            $this->visitChildren($child);
        }
    }

    public function testGetLocalizationsCount()
    {
        $result = $this->getRepository()->getLocalizationsCount();

        $this->assertIsInt($result);
        $this->assertEquals(3, $result);
    }

    public function testGetBatchIterator()
    {
        $expectedLocalizations = [$this->getDefaultLocalization()->getTitle()];
        foreach (LoadLocalizationData::getLocalizations() as $localization) {
            $expectedLocalizations[] = $localization['title'];
        }

        $localizations = [];
        foreach ($this->getRepository()->getBatchIterator() as $localization) {
            $localizations[] = $localization->getTitle();
        }

        $this->assertEquals($expectedLocalizations, $localizations);
    }

    public function testFindOneByLanguageCodeAndFormattingCode()
    {
        $this->assertTrue(null === $this->getRepository()->findOneByLanguageCodeAndFormattingCode('mx', 'mx'));

        $localization = $this->getRepository()->findOneByLanguageCodeAndFormattingCode('en_CA', 'en_CA');

        $this->assertFalse(null === $localization);
        $this->assertEquals('English (Canada)', $localization->getDefaultTitle());
    }

    /**
     * @return object|Localization
     */
    private function getDefaultLocalization()
    {
        $localeSettings = $this->getContainer()->get('oro_locale.settings');
        $locale = $localeSettings->getLocale();
        [$language] = explode('_', $locale);

        return $this->getRepository()->findOneBy([
            'language' => $this->getReference('language.' . $language),
            'formattingCode' => $locale
        ]);
    }

    public function testFindAllIndexedById(): void
    {
        $result = $this->getRepository()->findAllIndexedById();

        $localizationEnCa = $this->getReference('en_CA');
        $localizationDefault = $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE);
        $localizationEs = $this->getReference('es');

        $this->assertSame(
            [
                $localizationEnCa->getId() => $localizationEnCa,
                $localizationDefault->getId() => $localizationDefault,
                $localizationEs->getId() => $localizationEs
            ],
            $result
        );
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTranslations::class, LoadLanguages::class]);
    }

    private function getRepository(): TranslationRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Translation::class);
    }

    /**
     * @dataProvider getCountByLanguageProvider
     */
    public function testGetCountByLanguage(int $expectedCount, string $code)
    {
        $this->assertEquals($expectedCount, $this->getRepository()->getCountByLanguage($this->getReference($code)));
    }

    public function getCountByLanguageProvider(): array
    {
        return [
            'language1' => [
                'count' => 2,
                'code' => LoadLanguages::LANGUAGE1,
            ],
            'language2' => [
                'count' => 3,
                'code' => LoadLanguages::LANGUAGE2,
            ],
        ];
    }

    public function testDeleteByLanguage()
    {
        /* @var Language $language */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);

        $this->getRepository()->deleteByLanguage($language);

        $this->assertEquals(0, $this->getRepository()->getCountByLanguage($language));
    }

    /**
     * @dataProvider findValuesProvider
     */
    public function testFindValues(string $keyPrefix, string $locale, string $domain, array $values)
    {
        $this->assertEquals($values, $this->getRepository()->findValues($keyPrefix, $locale, $domain));
    }

    public function findValuesProvider(): array
    {
        return [
            'language2' => [
                'prefix' => 'translation.',
                'locale' => LoadLanguages::LANGUAGE2,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'values' => [
                    LoadTranslations::TRANSLATION3 => LoadTranslations::TRANSLATION3,
                    LoadTranslations::TRANSLATION4 => LoadTranslations::TRANSLATION4,
                    LoadTranslations::TRANSLATION5 => LoadTranslations::TRANSLATION5,
                ],
            ],
            'no translations' => [
                'prefix' => 'unknown_key_prefix',
                'locale' => LoadLanguages::LANGUAGE2,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'values' => [],
            ],
        ];
    }

    /**
     * @dataProvider findTranslationDataProvider
     */
    public function testFindTranslation(string $key, string $locale, string $domain, bool $hasResult = false)
    {
        if ($hasResult) {
            $this->assertEquals(
                $this->getReference($key),
                $this->getRepository()->findTranslation($key, $locale, $domain)
            );
        } else {
            $this->assertNull($this->getRepository()->findTranslation($key, $locale, $domain));
        }
    }

    public function findTranslationDataProvider(): array
    {
        return [
            'existing' => [
                'key' => LoadTranslations::TRANSLATION_KEY_3,
                'locale' => LoadLanguages::LANGUAGE2,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'expected' => true,
            ],
            'not_existing_key' => [
                'key' => '__NON__EXISTING__KEY__',
                'locale' => LoadLanguages::LANGUAGE2,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'expected' => false
            ],
            'not_existing_domain' => [
                'key' => LoadTranslations::TRANSLATION_KEY_3,
                'locale' => LoadLanguages::LANGUAGE2,
                'domain' => '__NON__EXISTING__DOMAIN__',
                'expected' => false
            ],
            'not_existing_language' => [
                'key' => LoadTranslations::TRANSLATION_KEY_3,
                'locale' => '__NON__EXISTING__LANGUAGE__',
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'expected' => false
            ],
            'non_valid_params' => [
                'key' => '__NON__EXISTING__KEY__',
                'locale' => '__NON__EXISTING__LANGUAGE__',
                'domain' => '__NON__EXISTING__DOMAIN__',
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider getTranslationsDataProvider
     */
    public function testGetTranslationsData(string $languageCode, array $expectedTranslations)
    {
        $language = $this->getReference($languageCode);

        $result = [];
        foreach ($expectedTranslations as $translationRef) {
            /** @var Translation $translation */
            $translation = $this->getReference($translationRef);
            $id = $translation->getTranslationKey()->getId();
            $result[$id] = [
                'translation_key_id' => $id,
                'scope' => $translation->getScope(),
                'value' => $translation->getValue(),
            ];
        }

        $this->assertEquals($result, $this->getRepository()->getTranslationsData($language->getId()));
    }

    public function getTranslationsDataProvider(): array
    {
        return [
            'language2' => [
                'languageCode' => LoadLanguages::LANGUAGE2,
                'values' => [
                    LoadTranslations::TRANSLATION3,
                    LoadTranslations::TRANSLATION4,
                    LoadTranslations::TRANSLATION5,
                ],
            ],
        ];
    }
}

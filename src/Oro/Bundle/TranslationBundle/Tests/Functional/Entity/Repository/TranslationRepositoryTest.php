<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var TranslationRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class, LoadLanguages::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Translation::class);
        $this->repository = $this->em->getRepository(Translation::class);
    }

    /**
     * @param int $expectedCount
     * @param string $code
     *
     * @dataProvider getCountByLanguageProvider
     */
    public function testGetCountByLanguage($expectedCount, $code)
    {
        $this->assertEquals($expectedCount, $this->repository->getCountByLanguage($this->getReference($code)));
    }

    /**
     * @return array
     */
    public function getCountByLanguageProvider()
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
        /* @var $language Language */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);

        $this->repository->deleteByLanguage($language);

        $this->assertEquals(0, $this->repository->getCountByLanguage($language));
    }

    /**
     * @param string $keyPrefix
     * @param string $locale
     * @param string $domain
     * @param array $values
     *
     * @dataProvider findValuesProvider
     */
    public function testFindValues($keyPrefix, $locale, $domain, array $values)
    {
        $this->assertEquals($values, $this->repository->findValues($keyPrefix, $locale, $domain));
    }

    /**
     * @return array
     */
    public function findValuesProvider()
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
     *
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @param bool $hasResult
     */
    public function testFindTranslation($key, $locale, $domain, $hasResult = false)
    {
        if (!$hasResult) {
            $this->assertNull($this->repository->findTranslation($key, $locale, $domain));
        } else {
            $this->assertEquals($this->getReference($key), $this->repository->findTranslation($key, $locale, $domain));
        }
    }

    /**
     * @return array
     */
    public function findTranslationDataProvider()
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

    public function testFindAllByLanguageAndDomain()
    {
        $result = array_column(
            $this->repository->findAllByLanguageAndDomain(
                LoadLanguages::LANGUAGE2,
                LoadTranslations::TRANSLATION_KEY_DOMAIN
            ),
            'key'
        );

        sort($result);

        $this->assertEquals(
            [
                LoadTranslations::TRANSLATION_KEY_4,
                LoadTranslations::TRANSLATION_KEY_5,
            ],
            $result
        );
    }

    /**
     * @dataProvider getTranslationsDataProvider
     *
     * @param string $languageCode
     * @param array $expectedTranslations
     */
    public function testGetTranslationsData($languageCode, array $expectedTranslations)
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

        $this->assertEquals($result, $this->repository->getTranslationsData($language->getId()));
    }

    /**
     * @return array
     */
    public function getTranslationsDataProvider()
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

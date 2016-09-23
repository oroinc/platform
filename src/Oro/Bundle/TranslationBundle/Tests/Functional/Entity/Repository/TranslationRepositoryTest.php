<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

/**
 * @dbIsolation
 */
class TranslationRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var TranslationRepository */
    protected $repository;

    /** @var Translation */
    protected $expectedForFind;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Translation::class);
        $this->repository = $this->em->getRepository(Translation::class);

        $this->expectedForFind = $this->getReference(LoadTranslations::TRANSLATION3);
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
                'count' => 1,
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
     * @dataProvider findValueDataProvider
     *
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @param bool $hasResult
     */
    public function testFindValue($key, $locale, $domain, $hasResult = false)
    {
        if (!$hasResult) {
            $this->assertNull($this->repository->findValue($key, $locale, $domain));
        } else {
            $this->assertEquals($this->repository->findValue($key, $locale, $domain), $this->getReference($key));
        }
    }

    /**
     * @return array
     */
    public function findValueDataProvider()
    {
        return [
            'existing' =>
                [
                    'key' => LoadTranslations::TRANSLATION_KEY_3,
                    'locale' => LoadLanguages::LANGUAGE2,
                    'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                    'expected' => true,
                ],
            'not_existing_key' =>
                [
                    'key' => '__NON__EXISTING__KEY__',
                    'locale' => LoadLanguages::LANGUAGE2,
                    'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                    'expected' => false
                ],
            'not_existing_domain' =>
                [
                    'key' => LoadTranslations::TRANSLATION_KEY_3,
                    'locale' => LoadLanguages::LANGUAGE2,
                    'domain' => '__NON__EXISTING__DOMAIN__',
                    'expected' => false
                ],
            'not_existing_language' =>
                [
                    'key' => LoadTranslations::TRANSLATION_KEY_3,
                    'locale' => '__NON__EXISTING__LANGUAGE__',
                    'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                    'expected' => false
                ],
            'non_valid_params' =>
                [
                    'key' => '__NON__EXISTING__KEY__',
                    'locale' => '__NON__EXISTING__LANGUAGE__',
                    'domain' => '__NON__EXISTING__DOMAIN__',
                    'expected' => false
                ],
        ];
    }
}

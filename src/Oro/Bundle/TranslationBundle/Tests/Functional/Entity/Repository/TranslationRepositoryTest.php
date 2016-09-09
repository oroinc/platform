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

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class]);

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
}

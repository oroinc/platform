<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
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
     * @param string $locale
     *
     * @dataProvider getCountByLocaleProvider
     */
    public function testGetCountByLocale($expectedCount, $locale)
    {
        $this->assertEquals($expectedCount, $this->repository->getCountByLocale($locale));
    }

    /**
     * @return array
     */
    public function getCountByLocaleProvider()
    {
        return [
            'language1' => [
                'count' => 2,
                'locale' => LoadLanguages::LANGUAGE1,
            ],
            'language2' => [
                'count' => 1,
                'locale' => LoadLanguages::LANGUAGE2,
            ],
        ];
    }

    public function testDeleteByLocale()
    {
        $this->repository->deleteByLocale(LoadLanguages::LANGUAGE1);

        $this->assertEquals(0, $this->repository->getCountByLocale(LoadLanguages::LANGUAGE1));
    }
}

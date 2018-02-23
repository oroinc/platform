<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationKeyRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var TranslationKeyRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(TranslationKey::class);
        $this->repository = $this->em->getRepository(TranslationKey::class);
    }

    public function testGetCount()
    {
        $this->assertGreaterThanOrEqual(3, $this->repository->getCount());
    }

    public function testFindAvailableDomains()
    {
        $domains = $this->repository->findAvailableDomains();

        $this->assertContains('test_domain', $domains);
        $this->assertGreaterThanOrEqual(1, count($domains));
    }

    public function testGetTranslationKeysData()
    {
        $data = $this->repository->getTranslationKeysData();
        $this->assertArrayHasKey(LoadTranslations::TRANSLATION_KEY_DOMAIN, $data);
        $expectedTranslationKeys = [
            LoadTranslations::TRANSLATION_KEY_1,
            LoadTranslations::TRANSLATION_KEY_2,
            LoadTranslations::TRANSLATION_KEY_3,
            LoadTranslations::TRANSLATION_KEY_4,
            LoadTranslations::TRANSLATION_KEY_5,
        ];

        $this->assertEquals($expectedTranslationKeys, array_keys($data[LoadTranslations::TRANSLATION_KEY_DOMAIN]));
    }
}

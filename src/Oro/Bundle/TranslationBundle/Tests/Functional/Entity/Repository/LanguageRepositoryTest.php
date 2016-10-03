<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

/**
 * @dbIsolation
 */
class LanguageRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var LanguageRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadLanguages::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Language::class);
        $this->repository = $this->em->getRepository(Language::class);
    }

    public function testGetAvailableLanguageCodes()
    {
        $this->assertEmpty(array_diff(
            [
                LoadLanguages::LANGUAGE1,
                LoadLanguages::LANGUAGE2,
            ],
            $this->repository->getAvailableLanguageCodes()
        ));
    }

    public function testGetEnabledAvailableLanguageCodes()
    {
        $this->assertEmpty(array_diff(
            [
                LoadLanguages::LANGUAGE2,
            ],
            $this->repository->getAvailableLanguageCodes(true)
        ));
    }
}

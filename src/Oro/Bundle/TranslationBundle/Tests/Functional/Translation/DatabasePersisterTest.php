<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Exception\LanguageNotFoundException;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var DatabasePersister */
    protected $persister;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class, LoadLanguages::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Translation::class);
        $this->persister = $this->getContainer()->get('oro_translation.database_translation.persister');
    }

    public function testPersist()
    {
        $catalogData = [
            'messages'   => [
                'key_1' => 'value_1',
                'key_2' => 'value_2',
                'key_3' => 'value_3',
            ],
            'validators' => [
                'key_1' => 'value_1',
                'key_2' => 'value_2',
            ]
        ];
        $keyCount = $this->getEntityCount(TranslationKey::class);
        $translationCount = $this->getEntityCount(Translation::class);
        $this->persister->persist(LoadLanguages::LANGUAGE1, $catalogData);
        $this->assertEquals($keyCount + 5, $this->getEntityCount(TranslationKey::class));
        $this->assertEquals($translationCount + 5, $this->getEntityCount(Translation::class));
    }

    public function testPersistInvalidLanguage()
    {
        $this->expectException(LanguageNotFoundException::class);
        $this->expectExceptionMessage('Language "NotExisted" not found');
        $this->persister->persist('NotExisted', []);
    }

    /**
     * @param string $class
     * @return int
     */
    private function getEntityCount($class)
    {
        return (int) $this->em
            ->getRepository($class)
            ->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

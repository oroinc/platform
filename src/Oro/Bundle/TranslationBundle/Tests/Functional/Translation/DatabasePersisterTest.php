<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Exception\LanguageNotFoundException;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTranslations::class, LoadLanguages::class]);
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
        $this->getPersister()->persist(LoadLanguages::LANGUAGE1, $catalogData);
        $this->assertEquals($keyCount + 5, $this->getEntityCount(TranslationKey::class));
        $this->assertEquals($translationCount + 5, $this->getEntityCount(Translation::class));
    }

    public function testPersistInvalidLanguage()
    {
        $this->expectException(LanguageNotFoundException::class);
        $this->expectExceptionMessage('Language "NotExisted" not found');
        $this->getPersister()->persist('NotExisted', []);
    }

    private function getPersister(): DatabasePersister
    {
        return $this->getContainer()->get('oro_translation.database_translation.persister');
    }

    private function getEntityCount(string $class): int
    {
        /** @var EntityRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository($class);

        return (int)$repo->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

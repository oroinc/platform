<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Exception\LanguageNotFoundException;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Component\Testing\ReflectionUtil;

class DatabasePersisterTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTranslations::class, LoadLanguages::class]);
    }

    public function testPersist(): void
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

    public function testPersistWithSystemScopeData(): void
    {
        $language = $this->getReference(LoadLanguages::LANGUAGE2);
        $translationKey = $this->getReference('tk-translation.trans5-test_domain');

        $catalogData = [
            'test_domain' => [
                'translation.trans5' => 'translation.trans5',
            ]
        ];
        $keyCount = $this->getEntityCount(TranslationKey::class);
        $translationCount = $this->getEntityCount(Translation::class);

        $this->getPersister()->persist(LoadLanguages::LANGUAGE2, $catalogData, Translation::SCOPE_SYSTEM);

        $this->assertEquals($keyCount, $this->getEntityCount(TranslationKey::class));
        $this->assertEquals($translationCount, $this->getEntityCount(Translation::class));
        $updatedEntity = $this->getTranslationEntity($language, $translationKey);
        self::assertEquals(Translation::SCOPE_SYSTEM, $updatedEntity->getScope());
        self::assertFalse($language->isLocalFilesLanguage());
    }

    public function testPersistWithSystemScopeDataAndLanguageShouldBeFilesBased(): void
    {
        //change the fileBasedLanguagesPath parameter to emulate the case when there is dumped translations
        //in translations directory.
        $translationsPath = realpath(__DIR__ . '/../Stub/translations');
        $helper = self::getContainer()->get('oro_translation.helper.file_based_language');
        ReflectionUtil::setPropertyValue($helper, 'fileBasedLanguagesPath', $translationsPath);

        $language = $this->getReference(LoadLanguages::LANGUAGE2);
        $translationKey = $this->getReference('tk-translation.trans5-test_domain');

        $catalogData = [
            'test_domain' => [
                'translation.trans5' => 'translation.trans5',
            ]
        ];
        $keyCount = $this->getEntityCount(TranslationKey::class);
        $translationCount = $this->getEntityCount(Translation::class);

        $this->getPersister()->persist(LoadLanguages::LANGUAGE2, $catalogData, Translation::SCOPE_SYSTEM);

        $this->assertEquals($keyCount, $this->getEntityCount(TranslationKey::class));
        $this->assertEquals($translationCount, $this->getEntityCount(Translation::class));
        $updatedEntity = $this->getTranslationEntity($language, $translationKey);
        self::assertEquals(Translation::SCOPE_SYSTEM, $updatedEntity->getScope());
        self::assertTrue($language->isLocalFilesLanguage());
    }

    public function testPersistInvalidLanguage(): void
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

    private function getTranslationEntity(Language $language, TranslationKey $key):Translation
    {
        /** @var EntityRepository $repo */
        return self::getContainer()->get('doctrine')
            ->getRepository(Translation::class)
            ->findOneBy(['language' => $language, 'translationKey' => $key]);
    }
}

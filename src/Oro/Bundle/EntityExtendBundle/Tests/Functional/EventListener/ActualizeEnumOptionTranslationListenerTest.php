<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadEnumOptionTranslationsData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class ActualizeEnumOptionTranslationListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadEnumOptionTranslationsData::class
        ]);

        $this->connection = self::getContainer()->get('doctrine')->getConnection();
    }

    public function testPostPersist()
    {
        $doctrine = self::getContainer()->get('doctrine');
        /** @var EntityRepository $repo */
        $repo = $doctrine->getManager()->getRepository(EnumOptionTranslation::class);
        $enumOptionTranslation = $repo->findOneBy(['foreignKey' => LoadEnumOptionTranslationsData::ENUM_OPTION_ID]);

        self::assertEquals('Bob Marley FR', $enumOptionTranslation->getContent());
    }

    public function testPostUpdate()
    {
        $translation = $this->getTranslationByEnumOptionId(
            LoadEnumOptionTranslationsData::ENUM_OPTION_ID,
            LoadEnumOptionTranslationsData::LANGUAGE_CODE
        );
        $translation->setValue('FR translation updated');

        self::getContainer()->get('doctrine')->getManager()->flush();

        $enumOptionTranslation = $this->getEnumOptionTranslationByForeignKey(
            LoadEnumOptionTranslationsData::ENUM_OPTION_ID,
            LoadEnumOptionTranslationsData::LANGUAGE_CODE
        );

        self::assertEquals('FR translation updated', $enumOptionTranslation->getContent());
    }

    public function testPostRemove()
    {
        $doctrine = self::getContainer()->get('doctrine');
        $translation = $this->getTranslationByEnumOptionId(
            LoadEnumOptionTranslationsData::ENUM_OPTION_ID,
            LoadEnumOptionTranslationsData::LANGUAGE_CODE
        );
        $doctrine->getManager()->remove($translation);
        $doctrine->getManager()->flush();

        $enumOptionTranslation = $this->getEnumOptionTranslationByForeignKey(
            LoadEnumOptionTranslationsData::ENUM_OPTION_ID,
            LoadEnumOptionTranslationsData::LANGUAGE_CODE
        );

        self::assertNull($enumOptionTranslation);
    }

    private function getTranslationByEnumOptionId(string $enumOptionId, string $languageCode): Translation
    {
        $doctrine = self::getContainer()->get('doctrine');
        $language = $doctrine->getManager()->getRepository(Language::class)->findOneBy(['code' => $languageCode]);

        return $doctrine
            ->getManager()
            ->getRepository(Translation::class)
            ->createQueryBuilder('t')
            ->join('t.translationKey', 'tk')
            ->where('tk.key = :translationKey')
            ->andWhere('t.language = :language')
            ->setParameter('translationKey', ExtendHelper::buildEnumOptionTranslationKey($enumOptionId))
            ->setParameter('language', $language)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function getEnumOptionTranslationByForeignKey(
        string $foreignKey,
        string $languageCode
    ): ?EnumOptionTranslation {
        return self::getContainer()->get('doctrine')
            ->getManager()
            ->getRepository(EnumOptionTranslation::class)
            ->findOneBy(['foreignKey' => $foreignKey, 'locale' => $languageCode]);
    }
}

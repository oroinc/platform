<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Load Test Enum Option Fixtures With translations
 */
class LoadEnumOptionsData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {

        $testEntity  = new \Extend\Entity\TestEntity1();
        $testEntity->setName('entity with enums');

        $testEnumField = new EnumOption(
            'test_enum_code',
            'testEnumField',
            'fredie_mercury',
            1
        );
        $testMultienumField = new EnumOption(
            'test_multienum_code',
            'testMultienumField',
            'bob_marley',
            1
        );

        // setting up enum fields
        $testEntity->testMultienumField = $testMultienumField;
        $testEntity->testEnumField = $testEnumField;

        $manager->persist($testEntity);
        $manager->persist($testEnumField);
        $manager->persist($testMultienumField);
        $manager->flush();

        $this->insertTranslationRecords($manager, $testEnumField);
        $this->insertTranslationRecords($manager, $testMultienumField);
    }

    private function insertTranslationRecords(ObjectManager $manager, EnumOption $enumOption): void
    {
        $connection = $manager->getConnection();

        $connection->executeQuery(
            'INSERT INTO oro_enum_option_trans (foreign_key, content, locale, object_class, field) 
             VALUES (?, ?, ?, ?, ?)',
            [$enumOption->getId(), $enumOption->getName(), 'en', EnumOption::class, 'enum']
        );
        $translationKey = ExtendHelper::buildEnumOptionTranslationKey($enumOption->getId());
        $connection->executeQuery(
            'INSERT INTO oro_translation_key (key, domain) VALUES (?, ?)',
            [$translationKey, 'enum_option']
        );
        $connection->executeQuery(
            'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) VALUES (?, ?, ?, ?)',
            [$connection->lastInsertId(), 1, $enumOption->getName(), 1]
        );
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Extend\Entity\TestEntity1;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Load Test Enum Option Fixtures With translations
 */
class LoadEnumOptionsData extends AbstractFixture
{
    public const string REFERENCE_KEY = 'test_entity_with_enums_777';

    private const string MULTIPLE_ENUM_CODE = 'test_multienum_code';
    private const string TEST_ENUM_CODE = 'test_enum_code';

    private const string TRANSLATION_LOCALE = 'en';
    private const string TRANSLATION_DOMAIN = 'enum_option';
    private const int TRANSLATION_SCOPE = 1;
    private const int DEFAULT_LANGUAGE_ID = 1;

    private const array SINGLE_ENUM_DATA = [
        'enumCode' => self::TEST_ENUM_CODE,
        'internalId' => 'testEnumField',
        'name' => 'fredie_mercury',
        'priority' => 1,
    ];

    private const array MULTIPLE_ENUM_DATA = [
        [
            'enumCode' => self::MULTIPLE_ENUM_CODE,
            'internalId' => '1945',
            'name' => 'bob_marley',
            'priority' => 1,
        ],
        [
            'enumCode' => self::MULTIPLE_ENUM_CODE,
            'internalId' => '1958',
            'name' => 'michael_jackson',
            'priority' => 2,
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $testEntity = $this->createTestEntity();

        $singleEnum = $this->createSingleEnumOption();
        $multipleEnums = $this->createMultipleEnumOptions();

        $testEntity->testEnumField = $singleEnum;
        $testEntity->testMultienumField = $multipleEnums;

        $this->persistEntities($manager, $testEntity, $singleEnum, $multipleEnums);
        $manager->flush();

        $this->createTranslations($manager, $singleEnum, $multipleEnums);
    }

    private function createTestEntity(): TestEntity1
    {
        $entity = new TestEntity1();
        $entity->setName('entity with enums');

        $this->setReference(self::REFERENCE_KEY, $entity);

        return $entity;
    }

    private function createSingleEnumOption(): EnumOption
    {
        return new EnumOption(
            self::SINGLE_ENUM_DATA['enumCode'],
            self::SINGLE_ENUM_DATA['internalId'],
            self::SINGLE_ENUM_DATA['name'],
            self::SINGLE_ENUM_DATA['priority']
        );
    }

    private function createMultipleEnumOptions(): array
    {
        return array_map(
            fn (array $data) => new EnumOption(
                $data['enumCode'],
                $data['internalId'],
                $data['name'],
                $data['priority']
            ),
            self::MULTIPLE_ENUM_DATA
        );
    }

    private function persistEntities(
        ObjectManager $manager,
        TestEntity1 $entity,
        EnumOption $singleEnum,
        array $multipleEnums
    ): void {
        $manager->persist($entity);
        $manager->persist($singleEnum);

        foreach ($multipleEnums as $option) {
            $manager->persist($option);
        }
    }

    private function createTranslations(ObjectManager $manager, EnumOption $singleEnum, array $multipleEnums): void
    {
        $this->insertTranslationRecords($manager, $singleEnum);

        foreach ($multipleEnums as $option) {
            $this->insertTranslationRecords($manager, $option);
        }
    }

    private function insertTranslationRecords(ObjectManager $manager, EnumOption $enumOption): void
    {
        $connection = $manager->getConnection();

        $this->insertEnumOptionTranslation($connection, $enumOption);
        $this->insertTranslationKey($connection, $enumOption);
        $this->insertTranslation($connection, $enumOption);
    }

    private function insertEnumOptionTranslation(Connection $connection, EnumOption $enumOption): void
    {
        $connection->executeStatement(
            'INSERT INTO oro_enum_option_trans (foreign_key, content, locale, object_class, field)'
                . ' VALUES (?, ?, ?, ?, ?)',
            [
                $enumOption->getId(),
                $enumOption->getName(),
                self::TRANSLATION_LOCALE,
                EnumOption::class,
                'enum'
            ]
        );
    }

    private function insertTranslationKey(Connection $connection, EnumOption $enumOption): void
    {
        $translationKey = ExtendHelper::buildEnumOptionTranslationKey($enumOption->getId());

        $connection->executeStatement(
            'INSERT INTO oro_translation_key (key, domain) VALUES (?, ?)',
            [$translationKey, self::TRANSLATION_DOMAIN]
        );
    }

    private function insertTranslation(Connection $connection, EnumOption $enumOption): void
    {
        $connection->executeStatement(
            'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) VALUES (?, ?, ?, ?)',
            [
                $connection->lastInsertId(),
                self::DEFAULT_LANGUAGE_ID,
                $enumOption->getName(),
                self::TRANSLATION_SCOPE,
            ]
        );
    }
}

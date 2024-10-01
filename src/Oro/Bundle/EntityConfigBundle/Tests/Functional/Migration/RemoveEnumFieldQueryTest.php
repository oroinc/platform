<?php

namespace Functional\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadEnumOptionsData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveEnumFieldQueryTest extends WebTestCase
{
    private Connection $connection;
    private ArrayLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadEnumOptionsData::class
        ]);
        $this->logger = new ArrayLogger();
        $this->connection = self::getContainer()->get('doctrine')->getConnection();
    }

    public function testGetDescription(): void
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'testEnumField';

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setContainer(self::getContainer());

        self::assertEquals(
            'Remove testEnumField enum field data',
            $migrationQuery->getDescription()
        );
    }

    public function testEntityNotFound(): void
    {
        $entityClass = \Extend\Entity\UnknownEntity::class;
        $entityField = 'testEnumField';

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->setContainer(self::getContainer());
        $migrationQuery->execute($this->logger);

        self::assertSame(
            [
                "Enum field 'testEnumField' from Entity 'Extend\Entity\UnknownEntity' is not found"
            ],
            $this->logger->getMessages()
        );
    }

    public function testFieldNotFound(): void
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'unknownEnumField';

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setContainer(self::getContainer());
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        self::assertSame(
            [
                "Enum field 'unknownEnumField' from Entity 'Extend\Entity\TestEntity1' is not found"
            ],
            $this->logger->getMessages()
        );
    }

    /**
     * @dataProvider enumFieldData
     */
    public function testExecute(string $entityClass, string $entityField): void
    {
        $fieldRow = $this->getFieldRow($entityClass, $entityField);
        $data = $this->connection->convertToPHPValue($fieldRow['data'], Types::ARRAY);
        $enumCode = $data['enum']['enum_code'];
        $enumOptions = $this->getEnumOptions($enumCode);

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );

        $migrationQuery->setContainer(self::getContainer());
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        self::assertFalse($this->getEnumOptions($enumCode));
        self::assertFalse($this->getFieldRow($entityClass, $entityField));

        if ($enumOptions) {
            if (!array_is_list($enumOptions)) {
                $enumOptions = [$enumOptions];
            }
            foreach ($enumOptions as $enumOption) {
                $translationKey = ExtendHelper::buildEnumOptionTranslationKey($enumOption['id']);
                self::assertFalse($this->getEnumOptionTranslation($enumOption['id']));
                self::assertFalse($this->getOroTranslationKey($translationKey));
            }
        }
    }

    public function enumFieldData(): array
    {
        return [
            'common enum field' => [
                'entityClass' => \Extend\Entity\TestEntity1::class,
                'entityField' => 'testEnumField',
            ],
            'multi-enum field' => [
                'entityClass' => \Extend\Entity\TestEntity1::class,
                'entityField' => 'testMultienumField',
            ]
        ];
    }

    private function getEntityRow(string $entityClass): array|bool
    {
        $getEntitySql = 'SELECT e.id, e.data 
                FROM oro_entity_config as e 
                WHERE e.class_name = ? 
                LIMIT 1';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$entityClass]
        );
    }

    private function getEnumOptionTranslation(string $enumOptionId): array|bool
    {
        $getEntitySql = 'SELECT * FROM oro_enum_option_trans WHERE foreign_key = ?';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$enumOptionId]
        );
    }

    private function getOroTranslationKey(string $translationKey): array|bool
    {
        $getEntitySql = 'SELECT *  FROM oro_translation_key WHERE key = ?';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$translationKey]
        );
    }

    private function getEnumOptions(string $enumCode): array|bool
    {
        $getEntitySql = 'SELECT id FROM oro_enum_option WHERE enum_code = ?';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$enumCode]
        );
    }

    private function getFieldRow(string $entityClass, string $entityField): array|bool
    {
        $getFieldSql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        return $this->connection->fetchAssociative(
            $getFieldSql,
            [
                $entityClass,
                $entityField
            ]
        );
    }
}

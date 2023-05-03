<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Extend\Entity\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadExtendedRelationsData;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\TestFrameworkBundle\Migrations\Schema\OroTestFrameworkBundleInstaller;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveEnumFieldQueryTest extends WebTestCase
{
    private Connection $connection;

    private ArrayLogger $logger;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadExtendedRelationsData::class]);

        $this->logger = new ArrayLogger();
        $this->connection = $this->getContainer()->get('doctrine')->getConnection();
    }

    public function testGetDescription(): void
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = OroTestFrameworkBundleInstaller::ENUM_FIELD_NAME;

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );

        $this->assertEquals(
            'Remove outdated testEnumField enum field data',
            $migrationQuery->getDescription()
        );
    }

    public function testExecuteEntityNotFound(): void
    {
        $entityClass = \Extend\Entity\UnknownEntity::class;
        $entityField = OroTestFrameworkBundleInstaller::ENUM_FIELD_NAME;

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertSame(
            [
                "Enum field 'testEnumField' from Entity 'Extend\Entity\UnknownEntity' is not found"
            ],
            $this->logger->getMessages()
        );
    }

    public function testExecuteFieldNotFound(): void
    {
        $entityClass = \Extend\Entity\TestEntity1::class;
        $entityField = 'unknownEnumField';

        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertSame(
            [
                "Enum field 'unknownEnumField' from Entity 'Extend\Entity\TestEntity1' is not found"
            ],
            $this->logger->getMessages()
        );
    }

    /**
     * @dataProvider enumFieldData
     */
    public function testExecute(string $entityClass, string $entityField, string $extendKeyPrefix): void
    {
        $fieldRow = $this->getFieldRow($entityClass, $entityField);
        $fieldData = $this->connection->convertToPHPValue($fieldRow['data'], Types::ARRAY);
        $enumClass = $fieldData['extend']['target_entity'];

        $entityRow = $this->getEntityRow($entityClass);
        $entityData = $this->connection->convertToPHPValue($entityRow['data'], Types::ARRAY);
        $extendKey = sprintf('%s|%s|%s|%s', $extendKeyPrefix, $entityClass, $enumClass, $entityField);
        unset(
            $entityData['extend']['relation'][$extendKey],
            $entityData['extend']['schema']['relation'][$entityField]
        );

        $enumEntityRow = $this->getEntityRow($enumClass);
        $enumClassId = $enumEntityRow['id'];
        $migrationQuery = new RemoveEnumFieldQuery(
            $entityClass,
            $entityField
        );

        $migrationQuery->setConnection($this->connection);
        $migrationQuery->execute($this->logger);

        $this->assertArrayIntersectEquals(
            [
                'DELETE FROM oro_entity_config_field WHERE id = ?',
                'Parameters:',
                '[1] = ' . $fieldRow['id'],
                'DELETE FROM oro_entity_config_field WHERE entity_id = ?',
                'Parameters:',
                '[1] = ' . $enumClassId,
                'DELETE FROM oro_entity_config WHERE class_name = ?',
                'Parameters:',
                '[1] = ' . $enumClass,
                'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
                'Parameters:',
                '[1] = ' . $this->connection->convertToDatabaseValue($entityData, Types::ARRAY),
                '[2] = ' . $entityClass,
            ],
            $this->logger->getMessages()
        );
    }

    public function enumFieldData(): array
    {
        return [
            'common enum field' => [
                'entityClass' => TestEntity1::class,
                'entityField' => OroTestFrameworkBundleInstaller::ENUM_FIELD_NAME,
                'extendKeyPrefix' => 'manyToOne'
            ],
            'multi-enum field' => [
                'entityClass' => TestEntity1::class,
                'entityField' => OroTestFrameworkBundleInstaller::MULTIENUM_FIELD_NAME,
                'extendKeyPrefix' => 'manyToMany'
            ]
        ];
    }

    private function getEntityRow(string $entityClass): array
    {
        $getEntitySql = 'SELECT e.id, e.data 
                FROM oro_entity_config as e 
                WHERE e.class_name = ? 
                LIMIT 1';

        return $this->connection->fetchAssoc(
            $getEntitySql,
            [$entityClass]
        );
    }

    private function getFieldRow(string $entityClass, string $entityField): array
    {
        $getFieldSql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        return $this->connection->fetchAssoc(
            $getFieldSql,
            [
                $entityClass,
                $entityField
            ]
        );
    }
}

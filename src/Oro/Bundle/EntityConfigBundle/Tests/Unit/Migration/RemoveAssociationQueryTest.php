<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveAssociationQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Psr\Log\LoggerInterface;

class RemoveAssociationQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var AbstractSchemaManager|\PHPUnit\Framework\MockObject\MockObject */
    private $schemaManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->connection->expects($this->any())
            ->method('getSchemaManager')
            ->willReturn($this->schemaManager);
    }

    public function testGetDescription()
    {
        $query = $this->createQuery(
            'Some\Source',
            'Some\Target',
            'some_association',
            'some_relation',
            true,
            'source_table',
            'target_table',
        );

        self::assertEquals(
            'Remove association relation from Some\Source entity to Some\Target '
            . '(association kind: some_association, relation type: some_relation, drop relation column/table: yes, '
            . 'source table: source_table, target table: target_table).',
            $query->getDescription()
        );
    }

    public function testExecuteThrowsExceptionForNonConfigurableEntity()
    {
        $this->connection->expects($this->any())
            ->method('fetchAssoc')
            ->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Source entity Some\Source is not a configurable entity.');

        $query = $this->createQuery(
            'Some\Source',
            'Some\Target',
            'some_association',
            'some_relation',
            true,
            'source_table',
            'target_table',
        );

        $query->execute($this->logger);
    }

    public function testEntityConfigIsUpdatedAndFieldConfigIsDeleted()
    {
        $dataWithRelation = [
            'extend' => [
                'relation' => ['some_relation|Some\Source|Some\Target|target_74573926' => 'something', 'x' => 'y'],
                'schema' => [
                    'relation' => ['target_74573926' => 'something', 'x' => 'y'],
                    'addremove' => ['target_74573926' => 'something', 'x' => 'y'],
                    'default' => ['default_target_74573926' => 'something', 'x' => 'y'],
                ]
            ]
        ];
        $dataWithoutRelation = [
            'extend' => [
                'relation' => ['x' => 'y'],
                'schema' => [
                    'relation' => ['x' => 'y'],
                    'addremove' => ['x' => 'y'],
                    'default' => ['x' => 'y'],
                ]
            ]
        ];

        $this->connection->expects($this->any())
            ->method('fetchAssoc')
            ->willReturn(['id' => '12345', 'data' => \serialize($dataWithRelation)]);
        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->with(\serialize($dataWithRelation), Types::ARRAY)
            ->willReturn($dataWithRelation);
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform());

        $this->logger->expects(self::exactly(9))
            ->method('info')
            ->withConsecutive(
                [\var_export($dataWithoutRelation, true)],
                ['UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name', []],
                ['Parameters:', []],
                ['[data] = ' . \serialize($dataWithoutRelation), []],
                ['[class_name] = Some\Source', []],
                ['DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name', []],
                ['Parameters:', []],
                ['[entity_id] = 12345', []],
                ['[field_name] = target_74573926', []]
            );

        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name',
                    ['data' => $dataWithoutRelation, 'class_name' => 'Some\Source'],
                    ['data' => Types::ARRAY, 'class_name' => Types::STRING]
                ],
                [
                    'DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name',
                    ['entity_id' => '12345', 'field_name' => 'target_74573926'],
                    ['entity_id' => Types::INTEGER, 'field_name' => Types::STRING],
                ],
            );

        $query = $this->createQuery(
            'Some\Source',
            'Some\Target',
            'some_association',
            'some_relation',
            false,
            'source_table',
            'target_table',
        );

        $query->execute($this->logger);
    }

    public function getPlatformDropConstraints(): array
    {
        return [
            [new MySqlPlatform(), 'ALTER TABLE source_table DROP FOREIGN KEY `FK_9876543210`'],
            [new PostgreSqlPlatform(), 'ALTER TABLE source_table DROP CONSTRAINT "FK_9876543210"']
        ];
    }

    /**
     * @dataProvider getPlatformDropConstraints
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDropRelationshipColumnForManyToOneRelation(AbstractPlatform $dbPlatform, string $dropFKSql)
    {
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($dbPlatform);

        $dataWithRelation = [
            'extend' => [
                'relation' => ['manyToOne|Some\Source|Some\Target|target_74573926' => 'something', 'x' => 'y'],
                'schema' => [
                    'relation' => ['target_74573926' => 'something', 'x' => 'y'],
                    'addremove' => ['target_74573926' => 'something', 'x' => 'y'],
                    'default' => ['default_target_74573926' => 'something', 'x' => 'y'],
                ]
            ]
        ];
        $dataWithoutRelation = [
            'extend' => [
                'relation' => ['x' => 'y'],
                'schema' => [
                    'relation' => ['x' => 'y'],
                    'addremove' => ['x' => 'y'],
                    'default' => ['x' => 'y'],
                ]
            ]
        ];

        $this->connection->expects($this->any())
            ->method('fetchAssoc')
            ->willReturn(['id' => '12345', 'data' => \serialize($dataWithRelation)]);
        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->with(\serialize($dataWithRelation), Types::ARRAY)
            ->willReturn($dataWithRelation);

        $targetTable = $this->createMock(Table::class);
        $targetTable->expects($this->any())
            ->method('getPrimaryKeyColumns')
            ->willReturn(['primary']);

        $sourceTable = $this->createMock(Table::class);
        $sourceTable->expects($this->any())
            ->method('hasColumn')
            ->with('target_74573926_primary')
            ->willReturn(true);

        $foreignKey = $this->createMock(ForeignKeyConstraint::class);
        $foreignKey->expects($this->any())
            ->method('getUnquotedLocalColumns')
            ->willReturn(['target_74573926_primary']);
        $foreignKey->expects($this->any())
            ->method('getName')
            ->willReturn('FK_9876543210');
        $sourceTable->expects($this->any())
            ->method('getForeignKeys')
            ->willReturn([$foreignKey]);

        $this->schemaManager->expects($this->any())
            ->method('listTableDetails')
            ->willReturnMap([
                ['source_table', $sourceTable],
                ['target_table', $targetTable],
            ]);

        $this->connection->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn ($id) => $dbPlatform->quoteIdentifier($id));

        $dropColumnSql = sprintf(
            'ALTER TABLE source_table DROP COLUMN %s',
            $dbPlatform->quoteIdentifier('target_74573926_primary')
        );

        $this->logger->expects(self::exactly(11))
            ->method('info')
            ->withConsecutive(
                [\var_export($dataWithoutRelation, true)],
                ['UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name', []],
                ['Parameters:', []],
                ['[data] = ' . \serialize($dataWithoutRelation), []],
                ['[class_name] = Some\Source', []],
                ['DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name', []],
                ['Parameters:', []],
                ['[entity_id] = 12345', []],
                ['[field_name] = target_74573926', []],
                [$dropFKSql, []],
                [$dropColumnSql, []]
            );

        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name',
                    ['data' => $dataWithoutRelation, 'class_name' => 'Some\Source'],
                    ['data' => Types::ARRAY, 'class_name' => Types::STRING]
                ],
                [
                    'DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name',
                    ['entity_id' => '12345', 'field_name' => 'target_74573926'],
                    ['entity_id' => Types::INTEGER, 'field_name' => Types::STRING],
                ],
            );

        $this->connection->expects(self::exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                [$dropFKSql],
                [$dropColumnSql]
            );

        $query = $this->createQuery(
            'Some\Source',
            'Some\Target',
            'some_association',
            RelationType::MANY_TO_ONE,
            true,
            'source_table',
            'target_table',
        );

        $query->execute($this->logger);
    }

    public function testDropRelationshipTableForManyToManyRelation()
    {
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform());

        $dataWithRelation = [
            'extend' => [
                'relation' => ['manyToMany|Some\Source|Some\Target|target_74573926' => 'something', 'x' => 'y'],
                'schema' => [
                    'relation' => ['target_74573926' => 'something', 'x' => 'y'],
                    'addremove' => ['target_74573926' => 'something', 'x' => 'y'],
                    'default' => ['default_target_74573926' => 'something', 'x' => 'y'],
                ]
            ]
        ];
        $dataWithoutRelation = [
            'extend' => [
                'relation' => ['x' => 'y'],
                'schema' => [
                    'relation' => ['x' => 'y'],
                    'addremove' => ['x' => 'y'],
                    'default' => ['x' => 'y'],
                ]
            ]
        ];

        $this->connection->expects($this->any())
            ->method('fetchAssoc')
            ->willReturn(['id' => '12345', 'data' => \serialize($dataWithRelation)]);
        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->with(\serialize($dataWithRelation), Types::ARRAY)
            ->willReturn($dataWithRelation);

        $this->schemaManager->expects($this->any())
            ->method('listTableNames')
            ->willReturn(['oro_rel_58267a4541c32acac0f56e']);

        $this->logger->expects(self::exactly(10))
            ->method('info')
            ->withConsecutive(
                [\var_export($dataWithoutRelation, true)],
                ['UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name', []],
                ['Parameters:', []],
                ['[data] = ' . \serialize($dataWithoutRelation), []],
                ['[class_name] = Some\Source', []],
                ['DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name', []],
                ['Parameters:', []],
                ['[entity_id] = 12345', []],
                ['[field_name] = target_74573926', []],
                ['DROP TABLE oro_rel_58267a4541c32acac0f56e', []],
            );

        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name',
                    ['data' => $dataWithoutRelation, 'class_name' => 'Some\Source'],
                    ['data' => Types::ARRAY, 'class_name' => Types::STRING]
                ],
                [
                    'DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name',
                    ['entity_id' => '12345', 'field_name' => 'target_74573926'],
                    ['entity_id' => Types::INTEGER, 'field_name' => Types::STRING],
                ],
            );

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with('DROP TABLE oro_rel_58267a4541c32acac0f56e');

        $query = $this->createQuery(
            'Some\Source',
            'Some\Target',
            'some_association',
            RelationType::MANY_TO_MANY,
            true,
            'source_table',
            'target_table',
        );

        $query->execute($this->logger);
    }

    private function createQuery(
        string $sourceEntityClass,
        string $targetEntityClass,
        string $associationKind,
        string $relationType,
        bool $dropRelationColumnsAndTables,
        string $sourceTableName,
        string $targetTableName
    ): RemoveAssociationQuery {
        $query = new class (
            $sourceEntityClass,
            $targetEntityClass,
            $associationKind,
            $relationType,
            $dropRelationColumnsAndTables,
            $sourceTableName,
            $targetTableName
        ) extends RemoveAssociationQuery {
            public function __construct(
                string $sourceEntityClass,
                string $targetEntityClass,
                string $associationKind,
                string $relationType,
                bool $dropRelationColumnsAndTables,
                string $sourceTableName,
                string $targetTableName
            ) {
                $this->sourceEntityClass = $sourceEntityClass;
                $this->targetEntityClass = $targetEntityClass;
                $this->associationKind = $associationKind;
                $this->relationType = $relationType;
                $this->dropRelationColumnsAndTables = $dropRelationColumnsAndTables;
                $this->sourceTableName = $sourceTableName;
                $this->targetTableName = $targetTableName;
            }
        };

        $query->setConnection($this->connection);

        return $query;
    }
}

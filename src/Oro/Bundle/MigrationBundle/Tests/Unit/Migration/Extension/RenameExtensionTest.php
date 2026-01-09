<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RenameExtensionTest extends TestCase
{
    /**
     * @dataProvider renameTableProvider
     */
    public function testRenameTable(AbstractPlatform $platform, string|array $expectedSql): void
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $schema = new Schema([new Table('old_table')]);
        $queries = new QueryBag();

        $extension->renameTable($schema, $queries, 'old_table', 'new_table');
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider renameTableWithSequencesProvider
     */
    public function testRenameTableWithSequences(AbstractPlatform $platform, array $expectedSql): void
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $table = new Table('old_table', [new Column('id', Type::getType(Types::INTEGER))]);
        $table->setPrimaryKey(['id']);

        $sequence = new Sequence($platform->getIdentitySequenceName('old_table', 'id'));

        $schema = new Schema([$table], [$sequence]);
        $queries = new QueryBag();

        $extension->renameTable($schema, $queries, 'old_table', 'new_table');

        $actualQueries = array_map(
            function (MigrationQuery $query) {
                return $query->getDescription();
            },
            $queries->getPostQueries()
        );
        $this->assertEquals($expectedSql, $actualQueries);
    }

    /**
     * @dataProvider renameColumnProvider
     */
    public function testRenameColumn(AbstractPlatform $platform, string $expectedSql): void
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $schema = new Schema(
            [
                new Table(
                    'test_table',
                    [
                        new Column(
                            'old_column',
                            Type::getType('string'),
                            ['length' => 100]
                        )
                    ]
                )
            ]
        );
        $queries = new QueryBag();
        $table = $schema->getTable('test_table');

        $extension->renameColumn($schema, $queries, $table, 'old_column', 'new_column');
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider addIndexProvider
     */
    public function testAddIndex(AbstractPlatform $platform, string $expectedSql): void
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);
        $extension->setNameGenerator(new DbIdentifierNameGenerator());

        $schema = new Schema();
        $queries = new QueryBag();

        $extension->addIndex($schema, $queries, 'test_table', ['new_column']);
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider addUniqueIndexProvider
     */
    public function testUniqueAddIndex(AbstractPlatform $platform, string $expectedSql): void
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);
        $extension->setNameGenerator(new DbIdentifierNameGenerator());

        $schema = new Schema();
        $queries = new QueryBag();

        $extension->addUniqueIndex($schema, $queries, 'test_table', ['new_column']);
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider addForeignKeyConstraintProvider
     */
    public function testAddForeignKeyConstraint(AbstractPlatform $platform, string $expectedSql): void
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);
        $extension->setNameGenerator(new DbIdentifierNameGenerator());

        $schema = new Schema();
        $queries = new QueryBag();

        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'test_table',
            'foreign_table',
            ['local_column'],
            ['foreign_column'],
            ['onDelete' => 'CASCADE']
        );

        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    public function renameTableProvider(): array
    {
        return [
            'mysql' => [new MySQLPlatform(), 'ALTER TABLE old_table RENAME TO new_table'],
            'mssql' => [
                new SQLServerPlatform(),
                [
                    "sp_rename 'old_table', 'new_table'",
                    "DECLARE @sql NVARCHAR(MAX) = N'';\n" .
                    "SELECT @sql += N'EXEC sp_rename N''' + dc.name + ''', N'''\n" .
                    "    + REPLACE(dc.name, '50BD45A0', 'EBFCC9B') + ''', ''OBJECT'';'\n" .
                    "    FROM sys.default_constraints dc\n" .
                    "    JOIN sys.tables tbl\n" .
                    "        ON dc.parent_object_id = tbl.object_id\n" .
                    "    WHERE tbl.name = 'new_table';\n" .
                    "EXEC sp_executesql @sql"
                ]
            ],
        ];
    }

    public function renameTableWithSequencesProvider(): array
    {
        return [
            'postgre' => [
                new PostgreSQLPlatform(),
                [
                    'ALTER TABLE old_table RENAME TO new_table',
                    'ALTER SEQUENCE old_table_id_seq RENAME TO new_table_id_seq'
                ]
            ],
            'oracle' => [
                new OraclePlatform(),
                [
                    'ALTER TABLE old_table RENAME TO new_table'
                ]
            ],
        ];
    }

    public function renameColumnProvider(): array
    {
        return [
            [new MySQLPlatform(), 'ALTER TABLE test_table CHANGE old_column new_column VARCHAR(100) NOT NULL'],
            [new PostgreSQLPlatform(), 'ALTER TABLE test_table RENAME COLUMN old_column TO new_column'],
            [new OraclePlatform(), 'ALTER TABLE test_table RENAME COLUMN old_column TO new_column'],
            [new SQLServerPlatform(), "sp_rename 'test_table.old_column', 'new_column', 'COLUMN'",],
        ];
    }

    public function addIndexProvider(): array
    {
        return [
            [new MySQLPlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
            [new PostgreSQLPlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
            [new OraclePlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
            [new SQLServerPlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
        ];
    }

    public function addUniqueIndexProvider(): array
    {
        return [
            [new MySQLPlatform(), 'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column)'],
            [new PostgreSQLPlatform(), 'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column)'],
            [new OraclePlatform(), 'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column)'],
            [
                new SQLServerPlatform(),
                'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column) WHERE new_column IS NOT NULL'
            ],
        ];
    }

    public function addForeignKeyConstraintProvider(): array
    {
        return [
            [
                new MySQLPlatform(),
                'ALTER TABLE test_table ADD CONSTRAINT fk_test_table_local_column '
                . 'FOREIGN KEY (local_column) REFERENCES foreign_table (foreign_column) ON DELETE CASCADE'
            ],
            [
                new PostgreSQLPlatform(),
                'ALTER TABLE test_table ADD CONSTRAINT fk_test_table_local_column '
                . 'FOREIGN KEY (local_column) REFERENCES foreign_table (foreign_column) ON DELETE CASCADE '
                . 'NOT DEFERRABLE INITIALLY IMMEDIATE'
            ],
            [
                new OraclePlatform(),
                'ALTER TABLE test_table ADD CONSTRAINT fk_test_table_local_column '
                . 'FOREIGN KEY (local_column) REFERENCES foreign_table (foreign_column) ON DELETE CASCADE'
            ],
            [
                new SQLServerPlatform(),
                'ALTER TABLE test_table ADD CONSTRAINT fk_test_table_local_column '
                . 'FOREIGN KEY (local_column) REFERENCES foreign_table (foreign_column) ON DELETE CASCADE'
            ],
        ];
    }
}

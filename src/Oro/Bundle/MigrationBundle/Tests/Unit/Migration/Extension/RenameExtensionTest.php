<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLServer2005Platform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class RenameExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider renameTableProvider
     *
     * @param AbstractPlatform $platform
     * @param string $expectedSql
     */
    public function testRenameTable(AbstractPlatform $platform, $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $schema  = new Schema([new Table('old_table')]);
        $queries = new QueryBag();

        $extension->renameTable($schema, $queries, 'old_table', 'new_table');
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider renameTableWithSequencesProvider
     *
     * @param AbstractPlatform $platform
     * @param array $expectedSql
     */
    public function testRenameTableWithSequences(AbstractPlatform $platform, array $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $table = new Table('old_table', [new Column('id', Type::getType(Type::INTEGER))]);
        $table->setPrimaryKey(['id']);

        $sequence = new Sequence($platform->getIdentitySequenceName('old_table', 'id'));

        $schema  = new Schema([$table], [$sequence]);
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
    public function testRenameColumn($platform, $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $schema  = new Schema(
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
        $table   = $schema->getTable('test_table');

        $extension->renameColumn($schema, $queries, $table, 'old_column', 'new_column');
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider addIndexProvider
     */
    public function testAddIndex($platform, $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);
        $extension->setNameGenerator(new DbIdentifierNameGenerator());

        $schema  = new Schema();
        $queries = new QueryBag();

        $extension->addIndex($schema, $queries, 'test_table', ['new_column']);
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider addUniqueIndexProvider
     */
    public function testUniqueAddIndex($platform, $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);
        $extension->setNameGenerator(new DbIdentifierNameGenerator());

        $schema  = new Schema();
        $queries = new QueryBag();

        $extension->addUniqueIndex($schema, $queries, 'test_table', ['new_column']);
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    /**
     * @dataProvider addForeignKeyConstraintProvider
     */
    public function testAddForeignKeyConstraint($platform, $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);
        $extension->setNameGenerator(new DbIdentifierNameGenerator());

        $schema  = new Schema();
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

    public function renameTableProvider()
    {
        return [
            'mysql' => [new MySqlPlatform(), 'ALTER TABLE old_table RENAME TO new_table'],
            'mssql' => [
                new SQLServer2005Platform(),
                [
                    "sp_RENAME 'old_table', 'new_table'",
                    "DECLARE @sql NVARCHAR(MAX) = N''; "
                    . "SELECT @sql += N'EXEC sp_rename N''' + dc.name + ''', N'''"
                    . " + REPLACE(dc.name, '50BD45A0', 'EBFCC9B') + ''', ''OBJECT'';' "
                    . "FROM sys.default_constraints dc JOIN sys.tables tbl ON dc.parent_object_id = tbl.object_id "
                    . "WHERE tbl.name = 'new_table';"
                    . "EXEC sp_executesql @sql"
                ]
            ],
        ];
    }

    public function renameTableWithSequencesProvider()
    {
        return [
            'postgre' => [
                new PostgreSqlPlatform(),
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

    public function renameColumnProvider()
    {
        return [
            [new MySqlPlatform(), 'ALTER TABLE test_table CHANGE old_column new_column VARCHAR(100) NOT NULL'],
            [new PostgreSqlPlatform(), 'ALTER TABLE test_table RENAME COLUMN old_column TO new_column'],
            [new OraclePlatform(), 'ALTER TABLE test_table RENAME COLUMN old_column TO new_column'],
            [new SQLServer2005Platform(), "sp_RENAME 'test_table.old_column', 'new_column', 'COLUMN'",],
        ];
    }

    public function addIndexProvider()
    {
        return [
            [new MySqlPlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
            [new PostgreSqlPlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
            [new OraclePlatform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
            [new SQLServer2005Platform(), 'CREATE INDEX idx_test_table_new_column ON test_table (new_column)'],
        ];
    }

    public function addUniqueIndexProvider()
    {
        return [
            [new MySqlPlatform(), 'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column)'],
            [new PostgreSqlPlatform(), 'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column)'],
            [new OraclePlatform(), 'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column)'],
            [
                new SQLServer2005Platform(),
                'CREATE UNIQUE INDEX uniq_test_table_new_column ON test_table (new_column) WHERE new_column IS NOT NULL'
            ],
        ];
    }

    public function addForeignKeyConstraintProvider()
    {
        return [
            [
                new MySqlPlatform(),
                'ALTER TABLE test_table ADD CONSTRAINT fk_test_table_local_column '
                . 'FOREIGN KEY (local_column) REFERENCES foreign_table (foreign_column) ON DELETE CASCADE'
            ],
            [
                new PostgreSqlPlatform(),
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
                new SQLServer2005Platform(),
                'ALTER TABLE test_table ADD CONSTRAINT fk_test_table_local_column '
                . 'FOREIGN KEY (local_column) REFERENCES foreign_table (foreign_column) ON DELETE CASCADE'
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class DbIdentifierNameGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider generateIndexNameProvider
     */
    public function testGenerateIndexName($tableName, $columnNames, $uniqueIndex, $expectedName)
    {
        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateIndexName($tableName, $columnNames, $uniqueIndex);
        $this->assertEquals($expectedName, $result);
    }

    /**
     * @dataProvider generateForeignKeyConstraintNameProvider
     */
    public function testGenerateForeignKeyConstraintName(
        $tableName,
        $columnNames,
        $foreignTableName,
        $foreignColumnNames,
        $expectedName
    ) {
        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateForeignKeyConstraintName(
            $tableName,
            $columnNames,
            $foreignTableName,
            $foreignColumnNames
        );
        $this->assertEquals($expectedName, $result);
    }

    /**
     * @dataProvider generateIdentifierNameProvider
     */
    public function testGenerateIdentifierName($tableNames, $columnNames, $prefix, $expectedName)
    {
        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateIdentifierName($columnNames, $prefix, $tableNames);
        $this->assertEquals($expectedName, $result);
    }

    public function generateIndexNameProvider()
    {
        return [
            ['table1', ['column1'], false, 'idx_table1_column1'],
            ['table1', ['column1'], true, 'uidx_table1_column1'],
            ['table1', ['column1', 'column2'], false, 'idx_table1_column1_column2'],
            ['table1', ['column1', 'column2'], true, 'uidx_table1_column1_column2'],
            ['table1', ['column1', 'column2', 'column3'], false, 'idx_341ce00bad15b1b1da1281271c'],
            ['table1', ['column1', 'column2', 'column3'], true, 'uidx_341ce00bad15b1b1da1281271'],
        ];
    }

    public function generateForeignKeyConstraintNameProvider()
    {
        return [
            ['table1', ['clmn1'], 'table2', ['clmn2'], 'fk_table1_table2_clmn1_clmn2'],
            ['table1', ['column1'], 'table2', ['column2'], 'fk_341ce00bad15b1b11c95229d859'],
            ['table1', ['c1', 'c2'], 'table2', ['id1', 'id2'], 'fk_table1_table2_c1_c2_id1_id2'],
            ['table1', ['column1', 'column2'], 'table2', ['id1', 'id2'], 'fk_341ce00bad15b1b1e8088724710'],
        ];
    }

    public function generateIdentifierNameProvider()
    {
        return [
            [
                [],
                ['column1'],
                'IDX',
                'idx_341ce00b'
            ],
            [
                ['table1'],
                ['column1'],
                'IDX',
                'idx_table1_column1'
            ],
            [
                ['table1', 'table2'],
                ['column1'],
                'FK',
                'fk_table1_table2_column1'
            ],
            [
                ['table1', 'table2'],
                ['column1'],
                'p1234567',
                'p1234567_table1_table2_column1'
            ],
            [
                ['table1', 'table2'],
                ['column1'],
                'p12345678',
                'p12345678_341ce00b1c95229d859c'
            ],
            [
                [],
                ['column1', 'column2'],
                'IDX',
                'idx_341ce00bad15b1b1'
            ],
            [
                ['table1'],
                ['column1', 'column2'],
                'IDX',
                'idx_table1_column1_column2'
            ],
            [
                ['table1', 'table2'],
                ['column1', 'column2'],
                'OroCRM',
                'orocrm_341ce00bad15b1b11c95229'
            ],
            [
                [],
                ['column1', 'column2', 'column3', 'column3'],
                'IDX',
                'idx_341ce00bad15b1b1da128127da'
            ],
            [
                ['table1'],
                ['column1', 'column2', 'column3', 'column3'],
                'IDX',
                'idx_341ce00bad15b1b1da128127da'
            ],
        ];
    }
}

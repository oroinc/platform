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
    public function testGenerateIdentifierName($tableNames, $columnNames, $prefix, $expectedName, $upperCase)
    {
        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateIdentifierName($tableNames, $columnNames, $prefix, $upperCase);
        $this->assertEquals($expectedName, $result);
    }

    /**
     * @dataProvider emptyTableNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A table name must not be empty.
     */
    public function testGenerateIdentifierNameWithEmptyTableName($tableNames)
    {
        $generator = new DbIdentifierNameGenerator();
        $generator->generateIdentifierName($tableNames, ['test'], 'test');
    }

    public function generateIndexNameProvider()
    {
        return [
            ['table1', ['column1'], false, 'idx_table1_column1'],
            ['table1', ['column1'], true, 'uidx_table1_column1'],
            ['table1', ['column1', 'column2'], false, 'idx_table1_column1_column2'],
            ['table1', ['column1', 'column2'], true, 'uidx_table1_column1_column2'],
            ['table1', ['column1', 'column2', 'column3'], false, 'IDX_1C95229D341CE00BAD15B1B1DA'],
            ['table1', ['column1', 'column2', 'column3'], true, 'UIDX_1C95229D341CE00BAD15B1B1D'],
        ];
    }

    public function generateForeignKeyConstraintNameProvider()
    {
        return [
            ['table1', ['clmn1'], 'table2', ['clmn2'], 'fk_table1_table2_clmn1_clmn2'],
            ['table1', ['column1'], 'table2', ['column2'], 'FK_1C95229D859C7327341CE00BAD1'],
            ['table1', ['c1', 'c2'], 'table2', ['id1', 'id2'], 'fk_table1_table2_c1_c2_id1_id2'],
            ['table1', ['column1', 'column2'], 'table2', ['id1', 'id2'], 'FK_1C95229D859C7327341CE00BAD1'],
        ];
    }

    public function generateIdentifierNameProvider()
    {
        return [
            [
                ['table1'],
                ['column1'],
                'IDX',
                'idx_table1_column1',
                null
            ],
            [
                ['table1', 'table2'],
                ['column1'],
                'FK',
                'fk_table1_table2_column1',
                null
            ],
            [
                ['table1', 'table2'],
                ['column1'],
                'p1234567',
                'p1234567_table1_table2_column1',
                null
            ],
            [
                ['table1', 'table2'],
                ['column1'],
                'p12345678',
                'P12345678_1C95229D859C7327341C',
                null
            ],
            [
                ['table1'],
                ['column1', 'column2'],
                'IDX',
                'idx_table1_column1_column2',
                null
            ],
            [
                ['table1', 'table2'],
                ['column1', 'column2'],
                'OroCRM',
                'orocrm_1c95229d859c7327341ce00',
                false
            ],
            [
                ['table1'],
                ['column1', 'column2', 'column3', 'column3'],
                'IDX',
                'IDX_1C95229D341CE00BAD15B1B1DA',
                null
            ],
        ];
    }

    public function emptyTableNameProvider()
    {
        return [
            [null],
            [''],
            [[]],
            [['']],
        ];
    }
}

<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
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

    public function testEncodedIndexNameIsTheSameAsDoctrineDefault()
    {
        $tableName  = 'tbl123456789012345';
        $columnName = 'clmn1234567890';

        $table = new Table($tableName, [new Column($columnName, Type::getType('string'))]);
        $table->addIndex([$columnName]);
        $indices = $table->getIndexes();
        $doctrineResult = array_pop($indices)->getName();

        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateIndexName($tableName, [$columnName]);

        $this->assertEquals($doctrineResult, $result);
    }

    public function testEncodedUniqueIndexNameIsTheSameAsDoctrineDefault()
    {
        $tableName  = 'tbl123456789012345';
        $columnName = 'clmn1234567890';

        $table = new Table($tableName, [new Column($columnName, Type::getType('string'))]);
        $table->addUniqueIndex([$columnName]);
        $indices = $table->getIndexes();
        $doctrineResult = array_pop($indices)->getName();

        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateIndexName($tableName, [$columnName], true);

        $this->assertEquals($doctrineResult, $result);
    }

    /**
     * @dataProvider generateForeignKeyConstraintNameProvider
     */
    public function testGenerateForeignKeyConstraintName(
        $tableName,
        $columnNames,
        $expectedName
    ) {
        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateForeignKeyConstraintName($tableName, $columnNames);
        $this->assertEquals($expectedName, $result);
    }

    public function testEncodedForeignKeyConstraintNameIsTheSameAsDoctrineDefault()
    {
        $tableName1  = 'tbl123456789012345';
        $columnName1 = 'clmn1234567890';

        $tableName2  = 'tbl1234567890';
        $columnName2 = 'clmn12345';

        $table1 = new Table($tableName1, [new Column($columnName1, Type::getType('integer'))]);
        $table2 = new Table($tableName2, [new Column($columnName2, Type::getType('integer'))]);
        $table2->setPrimaryKey([$columnName2]);

        $table1->addForeignKeyConstraint($table2, [$columnName1], [$columnName2]);

        $foreignKeys = $table1->getForeignKeys();
        $doctrineResult = array_pop($foreignKeys)->getName();

        $generator = new DbIdentifierNameGenerator();
        $result = $generator->generateForeignKeyConstraintName($tableName1, [$columnName1]);

        $this->assertEquals($doctrineResult, $result);
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
            ['table1', ['column1'], true, 'uniq_table1_column1'],
            ['table1', ['column1', 'column2'], false, 'idx_table1_column1_column2'],
            ['table1', ['column1', 'column2'], true, 'uniq_table1_column1_column2'],
            ['table1', ['column1', 'column2', 'column3'], false, 'IDX_1C95229D341CE00BAD15B1B1DA'],
            ['table1', ['column1', 'column2', 'column3'], true, 'UNIQ_1C95229D341CE00BAD15B1B1D'],
        ];
    }

    public function generateForeignKeyConstraintNameProvider()
    {
        return [
            ['table1', ['clmn1'], 'fk_table1_clmn1'],
            ['table1', ['column123456789012346'], 'FK_1C95229DCB68A266'],
            ['table1', ['c1', 'c2'], 'fk_table1_c1_c2'],
            ['table1', ['column1', 'column2', 'column3'], 'FK_1C95229D341CE00BAD15B1B1DA1'],
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

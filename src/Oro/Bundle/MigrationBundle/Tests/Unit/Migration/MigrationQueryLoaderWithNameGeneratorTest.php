<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Migration\v1_0\Test1BundleMigration10;
use Migration\v1_1\Test1BundleMigration11;
use TestPackage\src\WrongTableNameMigration;
use TestPackage\src\WrongColumnNameMigration;

class MigrationQueryLoaderWithNameGeneratorTest extends AbstractTestMigrationQueryLoader
{
    public function testGetMigrationsQueries()
    {
        $this->IncludeFile('Test1Bundle/Migrations/Schema/v1_0/Test1BundleMigration10.php');
        $this->IncludeFile('Test1Bundle/Migrations/Schema/v1_1/Test1BundleMigration11.php');
        $migrations = [
            new Test1BundleMigration10(),
            new Test1BundleMigration11()
        ];
        $queries    = $this->builder->getQueries($migrations);
        $this->assertEquals(2, count($queries));

        $test1BundleMigration10Data = $queries[0];
        $this->assertEquals('Migration\v1_0\Test1BundleMigration10', $test1BundleMigration10Data['migration']);
        $this->assertEquals(
            'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
            $test1BundleMigration10Data['queries'][0]
        );

        $test1BundleMigration11Data = $queries[1];
        $this->assertEquals('Migration\v1_1\Test1BundleMigration11', $test1BundleMigration11Data['migration']);
        $this->assertEquals(
            'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
            . 'COLLATE utf8_unicode_ci ENGINE = InnoDB',
            $test1BundleMigration11Data['queries'][0]
        );
        $this->assertEquals(
            'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            $test1BundleMigration11Data['queries'][1]
        );
    }

    public function testWrongTableNameQuery()
    {
        $this->IncludeFile('WrongTableNameMigration.php');
        $migrations = [new WrongTableNameMigration()];
        $this->setExpectedException(
            'Oro\Bundle\MigrationBundle\Exception\InvalidNameException',
            sprintf(
                'Max table name length is %s. Please correct "%s" table in "%s" migration',
                $this->nameGenerator->getMaxIdentifierSize(),
                'extra_long_table_name_bigger_than_30_chars',
                'TestPackage\src\WrongTableNameMigration'
            )
        );
        $this->builder->getQueries($migrations);
    }

    public function testWrongColumnNameQuery()
    {
        $this->includeFile('WrongColumnNameMigration.php');
        $migrations = [new WrongColumnNameMigration()];
        $this->setExpectedException(
            'Oro\Bundle\MigrationBundle\Exception\InvalidNameException',
            sprintf(
                'Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                $this->nameGenerator->getMaxIdentifierSize(),
                'wrong_table',
                'extra_long_column_bigger_30_chars',
                'TestPackage\src\WrongColumnNameMigration'
            )
        );
        $this->builder->getQueries($migrations);
    }
}

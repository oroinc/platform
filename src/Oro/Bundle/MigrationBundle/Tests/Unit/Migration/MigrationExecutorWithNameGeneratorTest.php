<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Migration\v1_0\Test1BundleMigration10;
use Migration\v1_1\Test1BundleMigration11;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\MigrationExecutorWithNameGenerator;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use TestPackage\src\WrongTableNameMigration;
use TestPackage\src\WrongColumnNameMigration;

class MigrationExecutorWithNameGeneratorTest extends AbstractTestMigrationExecutor
{
    /** @var MigrationExecutorWithNameGenerator */
    protected $executor;

    /** @var DbIdentifierNameGenerator */
    protected $nameGenerator;

    public function setUp()
    {
        parent::setUp();

        $this->nameGenerator = new DbIdentifierNameGenerator();

        $this->executor = new MigrationExecutorWithNameGenerator($this->queryExecutor);
        $this->executor->setLogger($this->logger);
        $this->executor->setNameGenerator($this->nameGenerator);
    }

    public function testExecuteUp()
    {
        $this->includeFile('Test1Bundle/Migrations/Schema/v1_0/Test1BundleMigration10.php');
        $this->includeFile('Test1Bundle/Migrations/Schema/v1_1/Test1BundleMigration11.php');
        $migrations = [
            new Test1BundleMigration10(),
            new Test1BundleMigration11()
        ];

        $this->connection->expects($this->at(2))
            ->method('executeQuery')
            ->with('CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)');
        $this->connection->expects($this->at(3))
            ->method('executeQuery')
            ->with(
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
        $this->connection->expects($this->at(4))
            ->method('executeQuery')
            ->with('ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL');

        $this->executor->executeUp($migrations);
        $messages = $this->logger->getMessages();
        $this->assertEquals(
            [
                '> Migration\v1_0\Test1BundleMigration10',
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
                '> Migration\v1_1\Test1BundleMigration11',
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE utf8_unicode_ci ENGINE = InnoDB',
                'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            ],
            $messages
        );
    }

    public function testExecuteUpWithDryRun()
    {
        $this->includeFile('Test1Bundle/Migrations/Schema/v1_0/Test1BundleMigration10.php');
        $this->includeFile('Test1Bundle/Migrations/Schema/v1_1/Test1BundleMigration11.php');
        $migrations = [
            new Test1BundleMigration10(),
            new Test1BundleMigration11()
        ];

        $this->connection->expects($this->never())
            ->method('executeQuery');

        $this->executor->executeUp($migrations, true);
        $messages = $this->logger->getMessages();
        $this->assertEquals(
            [
                '> Migration\v1_0\Test1BundleMigration10',
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
                '> Migration\v1_1\Test1BundleMigration11',
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE utf8_unicode_ci ENGINE = InnoDB',
                'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            ],
            $messages
        );
    }

    public function testWrongTableNameQuery()
    {
        $this->includeFile('WrongTableNameMigration.php');
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
        $this->executor->executeUp($migrations);
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
        $this->executor->executeUp($migrations);
    }
}

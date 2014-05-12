<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_0\Test1BundleMigration10;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_1\Test1BundleMigration11;

use Oro\Bundle\MigrationBundle\Migration\MigrationExecutorWithNameGenerator;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongTableNameMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongColumnNameMigration;

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
        $migration10 = new Test1BundleMigration10();
        $migration11 = new Test1BundleMigration11();
        $migrations = [
            $migration10,
            $migration11
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
                '> ' . get_class($migration10),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
                '> ' . get_class($migration11),
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE utf8_unicode_ci ENGINE = InnoDB',
                'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            ],
            $messages
        );
    }

    public function testExecuteUpWithDryRun()
    {
        $migration10 = new Test1BundleMigration10();
        $migration11 = new Test1BundleMigration11();
        $migrations = [
            $migration10,
            $migration11
        ];

        $this->connection->expects($this->never())
            ->method('executeQuery');

        $this->executor->executeUp($migrations, true);
        $messages = $this->logger->getMessages();
        $this->assertEquals(
            [
                '> ' . get_class($migration10),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
                '> ' . get_class($migration11),
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE utf8_unicode_ci ENGINE = InnoDB',
                'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            ],
            $messages
        );
    }

    public function testWrongTableNameQuery()
    {
        $migration = new WrongTableNameMigration();
        $migrations = [$migration];
        $this->setExpectedException(
            'Oro\Bundle\MigrationBundle\Exception\InvalidNameException',
            sprintf(
                'Max table name length is %s. Please correct "%s" table in "%s" migration',
                $this->nameGenerator->getMaxIdentifierSize(),
                'extra_long_table_name_bigger_than_30_chars',
                get_class($migration)
            )
        );
        $this->executor->executeUp($migrations);
    }

    public function testWrongColumnNameQuery()
    {
        $migration = new WrongColumnNameMigration();
        $migrations = [$migration];
        $this->setExpectedException(
            'Oro\Bundle\MigrationBundle\Exception\InvalidNameException',
            sprintf(
                'Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                $this->nameGenerator->getMaxIdentifierSize(),
                'wrong_table',
                'extra_long_column_bigger_30_chars',
                get_class($migration)
            )
        );
        $this->executor->executeUp($migrations);
    }
}

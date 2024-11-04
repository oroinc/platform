<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Oro\Bundle\MigrationBundle\Migration\MigrationExecutorWithNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_0\Test1BundleMigration10;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_1\Test1BundleMigration11;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongColumnNameMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongTableNameMigration;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class MigrationExecutorWithNameGeneratorTest extends MigrationExecutorTestCase
{
    private DbIdentifierNameGenerator $nameGenerator;
    private MigrationExecutorWithNameGenerator $executor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->nameGenerator = new DbIdentifierNameGenerator();

        $this->executor = new MigrationExecutorWithNameGenerator($this->queryExecutor, $this->cacheManager);
        $this->executor->setLogger($this->logger);
        $this->executor->setNameGenerator($this->nameGenerator);
    }

    public function testExecuteUp(): void
    {
        $migration10 = new Test1BundleMigration10();
        $migration11 = new Test1BundleMigration11();
        $migrations = [
            new MigrationState($migration10),
            new MigrationState($migration11)
        ];

        $this->connection->expects(self::exactly(3))
            ->method('executeQuery')
            ->withConsecutive(
                ['CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)'],
                [
                    'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                    . 'COLLATE `utf8_unicode_ci` ENGINE = InnoDB'
                ],
                ['ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL']
            );

        $this->executor->executeUp($migrations);
        $messages = array_values(array_filter($this->logger->getMessages(), function ($message) {
            return !preg_match("/\<comment\>.*MiB/", $message);
        }));
        $this->assertEquals(
            [
                '> ' . get_class($migration10),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
                '> ' . get_class($migration11),
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
                'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            ],
            $messages
        );
    }

    public function testExecuteUpWithDryRun(): void
    {
        $migration10 = new Test1BundleMigration10();
        $migration11 = new Test1BundleMigration11();
        $migrations = [
            new MigrationState($migration10),
            new MigrationState($migration11)
        ];

        $this->connection->expects(self::never())
            ->method('executeQuery');

        $this->executor->executeUp($migrations, true);
        $messages = array_values(array_filter($this->logger->getMessages(), function ($message) {
            return !preg_match("/\<comment\>.*MiB/", $message);
        }));
        $this->assertEquals(
            [
                '> ' . get_class($migration10),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
                '> ' . get_class($migration11),
                'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
                . 'COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
                'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            ],
            $messages
        );
    }

    public function testWrongTableNameQuery(): void
    {
        $migration = new WrongTableNameMigration();
        $migrations = [
            new MigrationState($migration)
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongTableNameMigration.'
        );
        $this->executor->executeUp($migrations);
        self::assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongTableNameMigration',
            $this->logger->getMessages()[0]
        );
        self::assertEquals(
            sprintf(
                '  ERROR: Max table name length is %s. Please correct "%s" table in "%s" migration',
                $this->nameGenerator->getMaxIdentifierSize(),
                'extra_long_table_name_which_are_bigger_than_63_different_characters',
                get_class($migration)
            ),
            $this->logger->getMessages()[1]
        );
    }

    public function testWrongColumnNameQuery(): void
    {
        $migration = new WrongColumnNameMigration();
        $migrations = [
            new MigrationState($migration)
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongColumnNameMigration.'
        );
        $this->executor->executeUp($migrations);
        self::assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\WrongColumnNameMigration',
            $this->logger->getMessages()[0]
        );
        self::assertEquals(
            sprintf(
                '  ERROR: Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                $this->nameGenerator->getMaxIdentifierSize(),
                'wrong_table',
                'extra_long_column_name_which_are_bigger_than_63_different_characters',
                get_class($migration)
            ),
            $this->logger->getMessages()[1]
        );
    }
}

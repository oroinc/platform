<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\MigrationExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\IndexMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\Test1BundleInstallation;

class MigrationExecutorTest extends AbstractTestMigrationExecutor
{
    /** @var MigrationExecutor */
    protected $executor;

    protected function setUp()
    {
        parent::setUp();

        $this->executor = new MigrationExecutor($this->queryExecutor);
        $this->executor->setLogger($this->logger);
    }

    public function testIndexesSuccessful()
    {
        $migrations = [
            new MigrationState(new IndexMigration()),
        ];

        $this->executor->executeUp($migrations);
    }

    public function testIndexFailed()
    {
        $migrations = ['InvalidIndexMigration'];
        $migrationsToExecute = [];
        foreach ($migrations as $migration) {
            $migrationClass = 'Oro\\Bundle\\MigrationBundle\\Tests\\Unit\\Fixture\\TestPackage\\' . $migration;
            $migrationsToExecute[] = new MigrationState(new $migrationClass());
        }

        $this->setExpectedException(
            '\RuntimeException',
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration.'
        );
        $this->executor->executeUp($migrationsToExecute);
        $this->assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration',
            $this->logger->getMessages()[0]
        );
        $this->assertEquals(
            '  ERROR: Could not create index for column with length more than 255.'
            . ' Please correct "key" column length "index_table" in table in'
            . ' "Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration" migration',
            $this->logger->getMessages()[1]
        );
    }

    public function testUpdatedColumnIndexFailed()
    {
        $migrations = ['IndexMigration', 'UpdatedColumnIndexMigration'];
        $migrationsToExecute = [];
        foreach ($migrations as $migration) {
            $migrationClass = 'Oro\\Bundle\\MigrationBundle\\Tests\\Unit\\Fixture\\TestPackage\\' . $migration;
            $migrationsToExecute[] = new MigrationState(new $migrationClass());
        }
        $migrationsToExecute[] = new MigrationState(new Test1BundleInstallation());

        $this->setExpectedException(
            '\RuntimeException',
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration.'
        );
        $this->executor->executeUp($migrationsToExecute);
        $this->assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration',
            $this->logger->getMessages()[2]
        );
        $this->assertEquals(
            '  ERROR: Could not create index for column with length more than 255.'
            . ' Please correct "key" column length "index_table2" in table in'
            . ' "Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration" migration',
            $this->logger->getMessages()[3]
        );
        $this->assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema'
            . '\Test1BundleInstallation - skipped',
            $this->logger->getMessages()[4]
        );
    }

    /**
     * @return Table[]
     */
    protected function getTables()
    {
        return [
            new Table(
                'index_table2',
                [
                    new Column(
                        'key',
                        Type::getType('string'),
                        [
                            'length' => 255
                        ]
                    )
                ]
            )
        ];
    }
}

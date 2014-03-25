<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\IndexMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration;

use Oro\Bundle\MigrationBundle\Migration\MigrationExecutor;

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
            new IndexMigration(),
        ];

        $this->executor->executeUp($migrations);
    }

    /**
     * @expectedException \Oro\Bundle\MigrationBundle\Exception\InvalidNameException
     * @expectedExceptionMessage Max index size is 255.
     * @dataProvider invalidMigrationsProvider
     */
    public function testIndexesFailed($migrations)
    {
        $migrationsToExecute = [];
        foreach ($migrations as $migration) {
            $migrationClass = 'Oro\\Bundle\\MigrationBundle\\Tests\\Unit\\Fixture\\TestPackage\\' . $migration;
            $migrationsToExecute[] = new $migrationClass();
        }

        $this->executor->executeUp($migrationsToExecute);
    }

    public function invalidMigrationsProvider()
    {
        return [
            'new'     => [
                'migrations' => ['InvalidIndexMigration']
            ],
            'updated' => [
                'migrations' => ['IndexMigration', 'UpdatedColumnIndexMigration']
            ]
        ];
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

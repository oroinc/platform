<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

use TestPackage\src\IndexMigration;

use Oro\Bundle\MigrationBundle\Migration\MigrationExecutor;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class MigrationExecutorTest extends AbstractTestMigrationExecutor
{
    /** @var MigrationExecutor */
    protected $executor;

    /** @var DbIdentifierNameGenerator */
    protected $nameGenerator;

    public function setUp($tables = [])
    {
        parent::setUp($this->getTables());

        $this->nameGenerator = new DbIdentifierNameGenerator();

        $this->executor = new MigrationExecutor($this->queryExecutor);
        $this->executor->setLogger($this->logger);
    }

    public function testIndexesSuccessful()
    {
        $this->IncludeFile('IndexMigration.php');

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
            $this->IncludeFile($migration . '.php');
            $migrationClass = 'TestPackage\\src\\' . $migration;
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

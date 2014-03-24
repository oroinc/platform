<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use TestPackage\src\IndexMigration;

use Oro\Bundle\MigrationBundle\Migration\MigrationExecutor;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class MigrationExecutorTest extends AbstractTestMigrationExecutor
{
    /** @var MigrationExecutor */
    protected $executor;

    /** @var DbIdentifierNameGenerator */
    protected $nameGenerator;

    public function setUp()
    {
        parent::setUp();

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
    public function testIndexesFailed($migration)
    {
        $this->IncludeFile($migration . '.php');

        $migrationClass = 'TestPackage\\src\\' . $migration;
        $migrations = [new $migrationClass()];

        $this->executor->executeUp($migrations);
    }

    public function invalidMigrationsProvider()
    {
        return [
            'new'     => ['InvalidIndexMigration'],
            'updated' => ['UpdatedColumnIndexMigration']
        ];
    }
}

<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\MigrationExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\IndexMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\Test1BundleInstallation;

class MigrationExecutorTest extends AbstractTestMigrationExecutor
{
    /** @var MigrationExecutor */
    protected $executor;

    protected function setUp()
    {
        parent::setUp();

        $this->executor = new MigrationExecutor($this->queryExecutor, $this->cacheManager);
        $this->executor->setLogger($this->logger);
    }

    public function testIndexesSuccessful()
    {
        $migrations = [
            new MigrationState(new IndexMigration()),
        ];
        $this->cacheManager->expects($this->once())
            ->method('clear');

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

        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage(
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration.'
        );
        $this->cacheManager->expects($this->never())
            ->method('clear');

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

        $this->expectException('\RuntimeException');
        $this->expectExceptionMessage(
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration.'
        );
        $this->cacheManager->expects($this->never())
            ->method('clear');

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

    public function testExecuteUpMigrationWithSchemaUpdate()
    {
        $schema = new Schema();

        $platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $schemaUpdateQuery = new SqlSchemaUpdateMigrationQuery('ALTER TABLE');

        $migration = $this->createMock('Oro\Bundle\MigrationBundle\Migration\Migration');
        $migration->expects($this->once())
            ->method('up')
            ->willReturnCallback(
                function (Schema $schema, QueryBag $queries) use ($schemaUpdateQuery) {
                    $queries->addQuery($schemaUpdateQuery);
                }
            );

        $this->assertEmpty($schema->getTables());
        $this->executor->executeUpMigration($schema, $platform, $migration);
        $this->assertNotEmpty($schema->getTables()); // schema was updated
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

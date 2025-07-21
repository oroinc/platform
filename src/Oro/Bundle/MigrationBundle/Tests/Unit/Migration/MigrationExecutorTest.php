<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\IndexMigration;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\Test1BundleInstallation;

class MigrationExecutorTest extends MigrationExecutorTestCase
{
    private const TEST_PACKAGE_NAMESPACE = 'Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\\';

    private MigrationExecutor $executor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new MigrationExecutor($this->queryExecutor, $this->cacheManager);
        $this->executor->setLogger($this->logger);
    }

    public function testIndexesSuccessful(): void
    {
        $migrations = [
            new MigrationState(new IndexMigration()),
        ];
        $this->cacheManager->expects(self::once())
            ->method('clear');

        $this->executor->executeUp($migrations);
    }

    public function testIndexFailed(): void
    {
        $migrations = ['InvalidIndexMigration'];
        $migrationsToExecute = [];
        foreach ($migrations as $migration) {
            $migrationClass = self::TEST_PACKAGE_NAMESPACE . $migration;
            $migrationsToExecute[] = new MigrationState(new $migrationClass());
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration.'
        );
        $this->cacheManager->expects(self::never())
            ->method('clear');

        $this->executor->executeUp($migrationsToExecute);
        self::assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration',
            $this->logger->getMessages()[0]
        );
        self::assertEquals(
            '  ERROR: Could not create index for column with length more than 255.'
            . ' Please correct "key" column length "index_table" in table in'
            . ' "Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\InvalidIndexMigration" migration',
            $this->logger->getMessages()[1]
        );
    }

    public function testUpdatedColumnIndexFailed(): void
    {
        $migrations = ['IndexMigration', 'UpdatedColumnIndexMigration'];
        $migrationsToExecute = [];
        foreach ($migrations as $migration) {
            $migrationClass = self::TEST_PACKAGE_NAMESPACE . $migration;
            $migrationsToExecute[] = new MigrationState(new $migrationClass());
        }
        $migrationsToExecute[] = new MigrationState(new Test1BundleInstallation());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed migrations: Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration.'
        );
        $this->cacheManager->expects(self::never())
            ->method('clear');

        $this->executor->executeUp($migrationsToExecute);
        self::assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration',
            $this->logger->getMessages()[2]
        );
        self::assertEquals(
            '  ERROR: Could not create index for column with length more than 255.'
            . ' Please correct "key" column length "index_table2" in table in'
            . ' "Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\UpdatedColumnIndexMigration" migration',
            $this->logger->getMessages()[3]
        );
        self::assertEquals(
            '> Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema'
            . '\Test1BundleInstallation - skipped',
            $this->logger->getMessages()[4]
        );
    }

    public function testExecuteUpMigrationWithSchemaUpdate(): void
    {
        $schema = new Schema();

        $platform = $this->createMock(AbstractPlatform::class);

        $schemaUpdateQuery = new SqlSchemaUpdateMigrationQuery('ALTER TABLE');

        $migration = $this->createMock(Migration::class);
        $migration->expects(self::once())
            ->method('up')
            ->willReturnCallback(function (Schema $schema, QueryBag $queries) use ($schemaUpdateQuery) {
                $queries->addQuery($schemaUpdateQuery);
            });

        self::assertEmpty($schema->getTables());
        $this->executor->executeUpMigration($schema, $platform, $migration);
        self::assertNotEmpty($schema->getTables()); // schema was updated
    }

    #[\Override]
    protected function getTables(): array
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

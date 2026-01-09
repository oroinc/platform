<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Event\PostUpMigrationLifeCycleEvent;
use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Migrations query executor.
 */
class MigrationExecutor
{
    protected const LENGTH_LIMIT_INDX_STRING = 620;

    protected MigrationQueryExecutorInterface $queryExecutor;
    protected OroDataCacheManager $cacheManager;
    protected LoggerInterface $logger;
    protected ?MigrationExtensionManager $extensionManager = null;
    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(MigrationQueryExecutorInterface $queryExecutor, OroDataCacheManager $cacheManager)
    {
        $this->queryExecutor = $queryExecutor;
        $this->cacheManager = $cacheManager;
        $this->logger = new NullLogger();
    }

    /**
     * Sets a logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        if (null !== $this->extensionManager) {
            $this->extensionManager->setLogger($this->logger);
        }
    }

    /**
     * Gets a query executor object this migration executor works with
     */
    public function getQueryExecutor(): MigrationQueryExecutorInterface
    {
        return $this->queryExecutor;
    }

    /**
     * Sets an extension manager.
     */
    public function setExtensionManager(MigrationExtensionManager $extensionManager): void
    {
        $this->extensionManager = $extensionManager;
        $connection = $this->queryExecutor->getConnection();
        $this->extensionManager->setConnection($connection);
        $this->extensionManager->setDatabasePlatform($connection->getDatabasePlatform());
        $this->extensionManager->setLogger($this->logger);
    }

    /**
     * Sets an event dispatcher.
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Executes UP method for the given migrations.
     *
     * @param MigrationState[] $migrations
     * @param bool $dryRun
     *
     * @throws \RuntimeException if at lease one migration failed
     */
    public function executeUp(array $migrations, bool $dryRun = false): void
    {
        $platform = $this->queryExecutor->getConnection()->getDatabasePlatform();
        $schema = $this->getActualSchema();
        $failedMigrations = [];

        foreach ($migrations as $item) {
            $migration = $item->getMigration();
            if (!empty($failedMigrations) && !$migration instanceof FailIndependentMigration) {
                $this->logger->info(sprintf('> %s - skipped', \get_class($migration)));
                continue;
            }

            if ($this->executeUpMigration($schema, $platform, $migration, $dryRun)) {
                $item->setSuccessful();
            } else {
                $item->setFailed();
                $failedMigrations[] = \get_class($migration);
            }

            $this->onPostUp($item);
        }

        if (!empty($failedMigrations)) {
            throw new \RuntimeException(sprintf('Failed migrations: %s.', implode(', ', $failedMigrations)));
        }

        $this->cacheManager->clear();
    }

    public function executeUpMigration(
        Schema &$schema,
        AbstractPlatform $platform,
        Migration $migration,
        bool $dryRun = false
    ): bool {
        $result = true;

        $name = \get_class($migration);
        $stopwatch = new Stopwatch();
        $stopwatch->start($name);

        $this->logger->info(sprintf('> %s', $name));

        $toSchema = clone $schema;
        $this->setExtensions($migration);
        try {
            $queryBag = new QueryBag();
            $migration->up($toSchema, $queryBag);

            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($schema, $toSchema);

            $this->checkTables($schemaDiff, $migration);
            $this->checkIndexes($schemaDiff, $migration);

            $queries = array_merge(
                $queryBag->getPreQueries(),
                $schemaDiff->toSql($platform),
                $queryBag->getPostQueries()
            );

            $schema = $toSchema;

            $isSchemaUpdateRequired = false;
            foreach ($queries as $query) {
                $this->queryExecutor->execute($query, $dryRun);
                if ($query instanceof SchemaUpdateQuery && !$isSchemaUpdateRequired && $query->isUpdateRequired()) {
                    $isSchemaUpdateRequired = true;
                }
            }

            if ($isSchemaUpdateRequired) {
                $schema = $this->getActualSchema();
            }
        } catch (\Exception $ex) {
            $result = false;
            $this->logger->error(sprintf('  ERROR: %s', $ex->getMessage()));
        }

        $stopwatch->stop($name);

        $this->logger->info(sprintf(
            '  <comment>%.2F MiB - %d ms</comment>',
            $stopwatch->getEvent($name)->getMemory() / 1024 / 1024,
            $stopwatch->getEvent($name)->getDuration()
        ));

        return $result;
    }

    /**
     * Creates a database schema object.
     *
     * @param Table[] $tables
     * @param Sequence[] $sequences
     * @param SchemaConfig|null $schemaConfig
     *
     * @return Schema
     */
    protected function createSchemaObject(
        array $tables = [],
        array $sequences = [],
        ?SchemaConfig $schemaConfig = null
    ): Schema {
        return new Schema($tables, $sequences, $schemaConfig);
    }

    /**
     * Sets extensions for the given migration.
     */
    protected function setExtensions(Migration $migration): void
    {
        if ($this->extensionManager) {
            $this->extensionManager->applyExtensions($migration);
        }
    }

    /**
     * Validates the given tables from SchemaDiff.
     *
     * @throws InvalidNameException if invalid table or column name is detected
     */
    protected function checkTables(SchemaDiff $schemaDiff, Migration $migration): void
    {
        foreach ($schemaDiff->newTables as $table) {
            $this->checkTableName($table->getName(), $migration);
            $this->checkColumnNames($table->getName(), $table->getColumns(), $migration);
        }

        foreach ($schemaDiff->changedTables as $tableName => $diff) {
            $this->checkColumnNames(
                $tableName,
                array_values($diff->addedColumns),
                $migration
            );
        }
    }

    /**
     * Validates the given columns.
     *
     * @param string $tableName
     * @param Column[] $columns
     * @param Migration $migration
     *
     * @throws InvalidNameException if invalid column name is detected
     */
    protected function checkColumnNames(string $tableName, array $columns, Migration $migration): void
    {
        foreach ($columns as $column) {
            $this->checkColumnName($tableName, $column->getName(), $migration);
        }
    }

    /**
     * Validates a table name.
     *
     * @throws InvalidNameException if table name is invalid
     */
    protected function checkTableName(string $tableName, Migration $migration): void
    {
    }

    /**
     * Validates a column name.
     *
     * @throws InvalidNameException if column name is invalid
     */
    protected function checkColumnName(string $tableName, string $columnName, Migration $migration): void
    {
    }

    protected function checkIndexes(SchemaDiff $schemaDiff, Migration $migration)
    {
        foreach ($schemaDiff->newTables as $table) {
            foreach ($table->getIndexes() as $index) {
                $this->checkIndex($table, $index, $migration);
            }
        }

        foreach ($schemaDiff->changedTables as $tableDiff) {
            foreach (array_values($tableDiff->addedIndexes) as $index) {
                $this->checkIndex(
                    $this->getTableFromDiff($tableDiff),
                    $index,
                    $migration
                );
            }
        }
    }

    /**
     * @throws InvalidNameException if index name is invalid
     */
    protected function checkIndex(Table $table, Index $index, Migration $migration): void
    {
        $columns = $index->getColumns();
        foreach ($columns as $columnName) {
            if ($table->getColumn($columnName)->getLength() > self::LENGTH_LIMIT_INDX_STRING) {
                throw new InvalidNameException(
                    sprintf(
                        'Could not create index for column with length more than %s. ' .
                        'Please correct "%s" column length "%s" in table in "%s" migration',
                        MySQLPlatform::LENGTH_LIMIT_TINYTEXT,
                        $columnName,
                        $table->getName(),
                        \get_class($migration)
                    )
                );
            }
        }
    }

    protected function getTableFromDiff(TableDiff $diff): Table
    {
        $changedColumns = array_map(
            function (ColumnDiff $columnDiff) {
                return $columnDiff->column;
            },
            $diff->changedColumns
        );

        return new Table(
            $diff->fromTable->getName(),
            array_merge($diff->fromTable->getColumns(), $diff->addedColumns, $changedColumns)
        );
    }

    protected function getActualSchema(): Schema
    {
        $platform = $this->queryExecutor->getConnection()->getDatabasePlatform();
        $sm = $this->queryExecutor->getConnection()->createSchemaManager();

        return $this->createSchemaObject(
            $sm->listTables(),
            $platform->supportsSequences() ? $sm->listSequences() : [],
            $sm->createSchemaConfig()
        );
    }

    protected function onPostUp(MigrationState $state): void
    {
        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                new PostUpMigrationLifeCycleEvent($state),
                MigrationEvents::MIGRATION_POST_UP
            );
        }
    }
}

<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Migrations query executor
 */
class MigrationExecutor
{
    /**
     * @var MigrationQueryExecutor
     */
    protected $queryExecutor;
    protected OroDataCacheManager $cacheManager;
    protected LoggerInterface $logger;
    protected ?MigrationExtensionManager $extensionManager = null;
    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(MigrationQueryExecutorInterface $queryExecutor, OroDataCacheManager $cacheManager)
    {
        $this->queryExecutor = $queryExecutor;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Sets a logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Gets a query executor object this migration executor works with
     *
     * @return MigrationQueryExecutor
     */
    public function getQueryExecutor()
    {
        return $this->queryExecutor;
    }

    /**
     * Sets extension manager
     */
    public function setExtensionManager(MigrationExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
        $connection = $this->queryExecutor->getConnection();
        $this->extensionManager->setConnection($connection);
        $this->extensionManager->setDatabasePlatform($connection->getDatabasePlatform());
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Executes UP method for the given migrations
     *
     * @param MigrationState[] $migrations
     * @param bool             $dryRun
     *
     * @throws \RuntimeException if at lease one migration failed
     */
    public function executeUp(array $migrations, $dryRun = false)
    {
        $platform = $this->queryExecutor->getConnection()->getDatabasePlatform();
        $schema = $this->getActualSchema();
        $failedMigrations = [];

        foreach ($migrations as $item) {
            $migration = $item->getMigration();
            if (!empty($failedMigrations) && !$migration instanceof FailIndependentMigration) {
                $this->logger->info(sprintf('> %s - skipped', get_class($migration)));
                continue;
            }

            if ($this->executeUpMigration($schema, $platform, $migration, $dryRun)) {
                $item->setSuccessful();
            } else {
                $item->setFailed();
                $failedMigrations[] = get_class($migration);
            }

            $this->onPostUp($item);
        }

        if (!empty($failedMigrations)) {
            throw new \RuntimeException(sprintf('Failed migrations: %s.', implode(', ', $failedMigrations)));
        }

        $this->cacheManager->clear();
    }

    /**
     * @param Schema           $schema
     * @param AbstractPlatform $platform
     * @param Migration        $migration
     * @param bool             $dryRun
     *
     * @return bool
     */
    public function executeUpMigration(
        Schema &$schema,
        AbstractPlatform $platform,
        Migration $migration,
        $dryRun = false
    ) {
        $result = true;

        $this->logger->info(sprintf('> %s', get_class($migration)));
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
                if (is_object($query) && $query instanceof SchemaUpdateQuery) {
                    // check if schema update required
                    if (!$isSchemaUpdateRequired && $query->isUpdateRequired()) {
                        $isSchemaUpdateRequired = true;
                    }
                }
            }

            if ($isSchemaUpdateRequired) {
                $schema = $this->getActualSchema();
            }
        } catch (\Exception $ex) {
            $result = false;
            $this->logger->error(sprintf('  ERROR: %s', $ex->getMessage()));
        }

        return $result;
    }

    /**
     * Creates a database schema object
     *
     * @param Table[]           $tables
     * @param Sequence[]        $sequences
     * @param SchemaConfig|null $schemaConfig
     *
     * @return Schema
     */
    protected function createSchemaObject(array $tables = [], array $sequences = [], $schemaConfig = null)
    {
        return new Schema($tables, $sequences, $schemaConfig);
    }

    /**
     * Sets extensions for the given migration
     */
    protected function setExtensions(Migration $migration)
    {
        if ($this->extensionManager) {
            $this->extensionManager->applyExtensions($migration);
        }
    }

    /**
     * Validates the given tables from SchemaDiff
     *
     * @throws InvalidNameException if invalid table or column name is detected
     */
    protected function checkTables(SchemaDiff $schemaDiff, Migration $migration)
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
     * Validates the given columns
     *
     * @param string    $tableName
     * @param Column[]  $columns
     * @param Migration $migration
     *
     * @throws InvalidNameException if invalid column name is detected
     */
    protected function checkColumnNames($tableName, $columns, Migration $migration)
    {
        foreach ($columns as $column) {
            $this->checkColumnName($tableName, $column->getName(), $migration);
        }
    }

    /**
     * Validates table name
     *
     * @param string    $tableName
     * @param Migration $migration
     *
     * @throws InvalidNameException if table name is invalid
     */
    protected function checkTableName($tableName, Migration $migration)
    {
    }

    /**
     * Validates column name
     *
     * @param string    $tableName
     * @param string    $columnName
     * @param Migration $migration
     *
     * @throws InvalidNameException if column name is invalid
     */
    protected function checkColumnName($tableName, $columnName, Migration $migration)
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
     * @throws InvalidNameException
     */
    protected function checkIndex(Table $table, Index $index, Migration $migration)
    {
        $columns = $index->getColumns();
        foreach ($columns as $columnName) {
            if ($table->getColumn($columnName)->getLength() > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
                throw new InvalidNameException(
                    sprintf(
                        'Could not create index for column with length more than %s. ' .
                        'Please correct "%s" column length "%s" in table in "%s" migration',
                        MySqlPlatform::LENGTH_LIMIT_TINYTEXT,
                        $columnName,
                        $table->getName(),
                        get_class($migration)
                    )
                );
            }
        }
    }

    /**
     * @param TableDiff $diff
     *
     * @return Table
     */
    protected function getTableFromDiff(TableDiff $diff)
    {
        $changedColumns = array_map(
            function (ColumnDiff $columnDiff) {
                return $columnDiff->column;
            },
            $diff->changedColumns
        );

        $table = new Table(
            $diff->fromTable->getName(),
            array_merge($diff->fromTable->getColumns(), $diff->addedColumns, $changedColumns)
        );

        return $table;
    }

    /**
     * @return Schema
     */
    protected function getActualSchema()
    {
        $platform = $this->queryExecutor->getConnection()->getDatabasePlatform();
        $sm = $this->queryExecutor->getConnection()->getSchemaManager();

        return $this->createSchemaObject(
            $sm->listTables(),
            $platform->supportsSequences() ? $sm->listSequences() : [],
            $sm->createSchemaConfig()
        );
    }

    protected function onPostUp(MigrationState $state): void
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                new PostUpMigrationLifeCycleEvent($state),
                MigrationEvents::MIGRATION_POST_UP
            );
        }
    }
}

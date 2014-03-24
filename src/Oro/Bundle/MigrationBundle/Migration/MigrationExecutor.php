<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Psr\Log\LoggerInterface;
use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;

class MigrationExecutor
{
    /**
     * @var MigrationQueryExecutor
     */
    protected $queryExecutor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MigrationExtensionManager
     */
    protected $extensionManager;

    /**
     * @param MigrationQueryExecutor $queryExecutor
     */
    public function __construct(MigrationQueryExecutor $queryExecutor)
    {
        $this->queryExecutor = $queryExecutor;
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
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
     *
     * @param MigrationExtensionManager $extensionManager
     */
    public function setExtensionManager(MigrationExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
        $this->extensionManager->setDatabasePlatform(
            $this->queryExecutor->getConnection()->getDatabasePlatform()
        );
    }

    /**
     * Executes UP method for the given migrations
     *
     * @param Migration[]     $migrations
     * @param bool            $dryRun
     * @throws InvalidNameException if invalid table or column name is detected
     */
    public function executeUp(array $migrations, $dryRun = false)
    {
        $platform   = $this->queryExecutor->getConnection()->getDatabasePlatform();
        $sm         = $this->queryExecutor->getConnection()->getSchemaManager();
        $fromSchema = $this->createSchemaObject(
            $sm->listTables(),
            $platform->supportsSequences() ? $sm->listSequences() : [],
            $sm->createSchemaConfig()
        );
        $queryBag   = new QueryBag();
        foreach ($migrations as $migration) {
            $toSchema = clone $fromSchema;

            $this->setExtensions($migration);
            $migration->up($toSchema, $queryBag);

            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);

            $this->checkTables($schemaDiff->newTables, $migration);
            $changedTables = $schemaDiff->changedTables;
            foreach ($changedTables as $tableName => $diff) {
                $this->checkColumnNames(
                    $tableName,
                    array_values($diff->addedColumns),
                    $migration
                );
            }

            $queries = array_merge(
                $queryBag->getPreQueries(),
                $schemaDiff->toSql($platform),
                $queryBag->getPostQueries()
            );

            $fromSchema = $toSchema;
            $queryBag->clear();

            $this->logger->notice(sprintf('> %s', get_class($migration)));
            foreach ($queries as $query) {
                $this->queryExecutor->execute($query, $dryRun);
            }
        }
    }

    /**
     * Creates a database schema object
     *
     * @param Table[]           $tables
     * @param Sequence[]        $sequences
     * @param SchemaConfig|null $schemaConfig
     * @return Schema
     */
    protected function createSchemaObject(array $tables = [], array $sequences = [], $schemaConfig = null)
    {
        return new Schema($tables, $sequences, $schemaConfig);
    }

    /**
     * Sets extensions for the given migration
     *
     * @param Migration $migration
     */
    protected function setExtensions(Migration $migration)
    {
        if ($this->extensionManager) {
            $this->extensionManager->applyExtensions($migration);
        }
    }

    /**
     * Validates the given tables
     *
     * @param Table[]   $tables
     * @param Migration $migration
     * @throws InvalidNameException if invalid table or column name is detected
     */
    protected function checkTables($tables, Migration $migration)
    {
        foreach ($tables as $table) {
            $this->checkTableName($table->getName(), $migration);
            $this->checkColumnNames($table->getName(), $table->getColumns(), $migration);
            $this->checkIndexes($table, $migration);
        }
    }

    /**
     * Validates the given columns
     *
     * @param string    $tableName
     * @param Column[]  $columns
     * @param Migration $migration
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
     * @throws InvalidNameException if column name is invalid
     */
    protected function checkColumnName($tableName, $columnName, Migration $migration)
    {
    }

    /**
     * @param Table     $table
     * @param Migration $migration
     */
    protected function checkIndexes($table, Migration $migration)
    {
        foreach ($table->getIndexes() as $index) {
            $this->checkIndex($table, $index, $migration);
        }
    }

    /**
     * @param Table     $table
     * @param Index     $index
     * @param Migration $migration
     * @throws InvalidNameException
     */
    protected function checkIndex(Table $table, Index $index, Migration $migration)
    {
        $columns = $index->getColumns();
        foreach ($columns as $columnName) {
            if ($table->getColumn($columnName)->getLength() > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
                throw new InvalidNameException(
                    sprintf(
                        'Max index size is %s. Please correct "%s:%s" column size in "%s" migration',
                        MySqlPlatform::LENGTH_LIMIT_TINYTEXT,
                        $table->getName(),
                        $columnName,
                        get_class($migration)
                    )
                );
            }
        }
    }
}

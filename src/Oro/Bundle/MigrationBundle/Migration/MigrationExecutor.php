<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Psr\Log\LoggerInterface;
use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;

class MigrationExecutor
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var MigrationExtensionManager
     */
    protected $extensionManager;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
            $this->connection->getDatabasePlatform()
        );
    }

    /**
     * Executes UP method for the given migrations
     *
     * @param Migration[]     $migrations
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     * @throws InvalidNameException if invalid table or column name is detected
     */
    public function executeUp(array $migrations, LoggerInterface $logger, $dryRun = false)
    {
        $platform   = $this->connection->getDatabasePlatform();
        $sm         = $this->connection->getSchemaManager();
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

            $logger->notice(sprintf('> %s', get_class($migration)));
            foreach ($queries as $query) {
                $this->executeQuery($query, $logger, $dryRun);
            }
        }
    }

    /**
     * Executes the given query
     *
     * @param string|MigrationQuery $query
     * @param LoggerInterface       $logger
     * @param bool                  $dryRun
     */
    protected function executeQuery($query, LoggerInterface $logger, $dryRun)
    {
        if ($query instanceof MigrationQuery) {
            if ($query instanceof ConnectionAwareInterface) {
                $query->setConnection($this->connection);
            }
            if ($dryRun) {
                $descriptions = $query->getDescription();
                foreach ((array)$descriptions as $description) {
                    $logger->notice($description);
                }
            } else {
                $query->execute($logger);
            }
        } else {
            $logger->notice($query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
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
}

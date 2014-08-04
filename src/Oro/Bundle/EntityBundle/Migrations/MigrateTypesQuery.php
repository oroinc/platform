<?php

namespace Oro\Bundle\EntityBundle\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class MigrateTypesQuery extends ParametrizedMigrationQuery
{
    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var ForeignKeyConstraint[]
     */
    protected $foreignKeys = [];


    /**
     * @var AbstractPlatform $platform
     * @var Schema $schema
     * @var string $tableName
     * @var string $columnName
     * @var string $type
     */
    public function __construct(AbstractPlatform $platform, Schema $schema, $tableName, $columnName, $type)
    {
        $this->platform = $platform;
        $this->schema = $schema;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->type = Type::getType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Change column type including all related columns');
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        if (empty($this->platform)) {
            throw new \InvalidArgumentException('Platform required');
        }

        /** Apply only for mysql platform */
        if ($this->platform->getName() !== 'mysql') {
            return;
        }

        if (empty($this->tableName) || empty($this->columnName) || empty($this->schema)) {
            throw new \InvalidArgumentException('Schema, table and column are required');
        }

        /** already applied */
        if ($this->getColumn($this->tableName, $this->columnName)->getType() == $this->type) {
            return;
        }

        $relatedColumnsData = $this->getRelatedColumnsData();

        foreach ($relatedColumnsData as $relatedColumnData) {
            $relatedTableName = $relatedColumnData['tableName'];
            $foreignKeyName = $relatedColumnData['constraintName'];
            $columnName = $relatedColumnData['columnName'];
            $relatedTable = $this->schema->getTable($relatedTableName);
            $column = $this->getColumn($relatedTableName, $columnName, $this->type);
            $foreignKey = $relatedTable->getForeignKey($foreignKeyName);

            $this->foreignKeys[$relatedTableName] = $foreignKey;

            $diff = new TableDiff($relatedTableName);
            $diff->changedColumns[] = new ColumnDiff($columnName, $column);
            $diff->removedForeignKeys[] = $foreignKey;
            $this->executeQueryFromDiff($diff, $logger, $dryRun);
        }

        $column = $this->getColumn($this->tableName, $this->columnName, $this->type);

        $diff = new TableDiff($this->tableName);
        $diff->changedColumns[] = new ColumnDiff($this->columnName, $column);
        $this->executeQueryFromDiff($diff, $logger, $dryRun);

        foreach ($this->foreignKeys as $relatedTableName => $foreignKey) {
            $diff = new TableDiff($relatedTableName);
            $diff->addedForeignKeys[] = $foreignKey;
            $this->executeQueryFromDiff($diff, $logger, $dryRun);
        }
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param Type $type
     * @return \Doctrine\DBAL\Schema\Column
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function getColumn($tableName, $columnName, $type = null)
    {
        $table = $this->schema->getTable($tableName);
        $column = $table->getColumn($columnName);
        if ($type) {
            $column->setType($this->type);
        }

        return $column;
    }

    /**
     * @param TableDiff $diff
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executeQueryFromDiff($diff, $logger, $dryRun)
    {
        $query = implode(';', $this->platform->getAlterTableSQL($diff));
        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeUpdate($query);
        }
    }

    /**
     * @return array
     */
    protected function getRelatedColumnsData()
    {
        $query = <<<SQL
SELECT
    TABLE_NAME as tableName,
    COLUMN_NAME as columnName,
    CONSTRAINT_NAME as constraintName
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = :schemaName
    AND REFERENCED_TABLE_NAME = :tableName
    AND REFERENCED_COLUMN_NAME = :columnName
SQL;

        return $this->connection->fetchAll(
            $query,
            [
                'schemaName' => $this->schema->getName(),
                'tableName' => $this->tableName,
                'columnName' => $this->columnName
            ],
            ['schemaName' => 'string', 'tableName' => 'string', 'columnName' => 'string']
        );
    }
}

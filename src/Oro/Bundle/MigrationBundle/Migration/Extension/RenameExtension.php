<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Schema\Column;
use Oro\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;

/**
 * Provides an ability to rename tables and columns,
 * and to create indexes and foreign key constraints without check of a table and columns existence.
 */
class RenameExtension implements DatabasePlatformAwareInterface, NameGeneratorAwareInterface
{
    use DatabasePlatformAwareTrait;
    use NameGeneratorAwareTrait;

    /**
     * Renames a table.
     */
    public function renameTable(Schema $schema, QueryBag $queries, string $oldTableName, string $newTableName): void
    {
        $table = $schema->getTable($oldTableName);
        $diff = new TableDiff($table->getName());
        $diff->newName = $newTableName;

        $renameQuery = new SqlSchemaUpdateMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );
        $queries->addQuery($renameQuery);

        if ($this->platform->supportsSequences()) {
            $primaryKey = $schema->getTable($oldTableName)->getPrimaryKeyColumns();
            if (count($primaryKey) === 1) {
                $primaryKey = reset($primaryKey);
                $oldSequenceName = $this->platform->getIdentitySequenceName($oldTableName, $primaryKey);
                if ($schema->hasSequence($oldSequenceName)) {
                    $newSequenceName = $this->platform->getIdentitySequenceName($newTableName, $primaryKey);
                    if ($this->platform instanceof PostgreSqlPlatform) {
                        $renameSequenceQuery = new SqlSchemaUpdateMigrationQuery(
                            "ALTER SEQUENCE $oldSequenceName RENAME TO $newSequenceName"
                        );
                        $queries->addQuery($renameSequenceQuery);
                    }
                }
            }
        }
    }

    /**
     * Renames a column.
     */
    public function renameColumn(
        Schema $schema,
        QueryBag $queries,
        Table $table,
        string $oldColumnName,
        string $newColumnName
    ): void {
        $column = new Column(['column' => $table->getColumn($oldColumnName)]);
        $column->changeName($newColumnName);
        $diff = new TableDiff($table->getName());
        $diff->renamedColumns = [$oldColumnName => $column];

        $renameQuery = new SqlSchemaUpdateMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );
        $queries->addQuery($renameQuery);
    }

    /**
     * Creates an index without check of table and columns existence.
     * This method can be helpful when you need to create an index for renamed table or column.
     *
     * @param Schema      $schema
     * @param QueryBag    $queries
     * @param string      $tableName
     * @param string[]    $columnNames
     * @param string|null $indexName
     */
    public function addIndex(
        Schema $schema,
        QueryBag $queries,
        string $tableName,
        array $columnNames,
        ?string $indexName = null
    ) {
        if (!$indexName) {
            $indexName = $this->nameGenerator->generateIndexName($tableName, $columnNames);
        }
        $index = new Index($indexName, $columnNames);
        $diff = new TableDiff($tableName);
        $diff->addedIndexes = [$indexName => $index];

        $renameQuery = new SqlSchemaUpdateMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Creates an unique index without check of table and columns existence.
     * This method can be helpful when you need to create an index for renamed table or column.
     *
     * @param Schema      $schema
     * @param QueryBag    $queries
     * @param string      $tableName
     * @param string[]    $columnNames
     * @param string|null $indexName
     */
    public function addUniqueIndex(
        Schema $schema,
        QueryBag $queries,
        string $tableName,
        array $columnNames,
        ?string $indexName = null
    ) {
        if (!$indexName) {
            $indexName = $this->nameGenerator->generateIndexName($tableName, $columnNames, true);
        }
        $index = new Index($indexName, $columnNames, true);
        $diff = new TableDiff($tableName);
        $diff->addedIndexes = [$indexName => $index];

        $renameQuery = new SqlSchemaUpdateMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Creates a foreign key constraint without check of table and columns existence.
     * This method can be helpful when you need to create a constraint for renamed table or column.
     *
     * @param Schema      $schema
     * @param QueryBag    $queries
     * @param string      $tableName
     * @param string      $foreignTable
     * @param string[]    $localColumnNames
     * @param string[]    $foreignColumnNames
     * @param array       $options
     * @param string|null $constraintName
     */
    public function addForeignKeyConstraint(
        Schema $schema,
        QueryBag $queries,
        string $tableName,
        string $foreignTable,
        array $localColumnNames,
        array $foreignColumnNames,
        array $options = [],
        ?string $constraintName = null
    ): void {
        if (!$constraintName) {
            $constraintName = $this->nameGenerator->generateForeignKeyConstraintName(
                $tableName,
                $localColumnNames
            );
        }
        $constraint = new ForeignKeyConstraint(
            $localColumnNames,
            $foreignTable,
            $foreignColumnNames,
            $constraintName,
            $options
        );
        $diff = new TableDiff($tableName);
        $diff->addedForeignKeys = [$constraintName => $constraint];

        $renameQuery = new SqlSchemaUpdateMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }
}

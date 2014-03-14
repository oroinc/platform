<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Schema\Column;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class RenameExtension implements DatabasePlatformAwareInterface, NameGeneratorAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Renames a table
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $oldTableName
     * @param string   $newTableName
     */
    public function renameTable(Schema $schema, QueryBag $queries, $oldTableName, $newTableName)
    {
        $table         = $schema->getTable($oldTableName);
        $diff          = new TableDiff($table->getName());
        $diff->newName = $newTableName;

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Renames a column
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param Table    $table
     * @param string   $oldColumnName
     * @param string   $newColumnName
     */
    public function renameColumn(Schema $schema, QueryBag $queries, Table $table, $oldColumnName, $newColumnName)
    {
        $column = new Column(['column' => $table->getColumn($oldColumnName)]);
        $column->changeName($newColumnName);
        $diff                 = new TableDiff($table->getName());
        $diff->renamedColumns = [$oldColumnName => $column];

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Create an index without check of table and columns existence.
     * This method can be helpful when you need to create an index for renamed table or column
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
        $tableName,
        array $columnNames,
        $indexName = null
    ) {
        if (!$indexName) {
            $indexName = $this->nameGenerator->generateIndexName($tableName, $columnNames);
        }
        $index              = new Index($indexName, $columnNames);
        $diff               = new TableDiff($tableName);
        $diff->addedIndexes = [$indexName => $index];

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Create an unique index without check of table and columns existence.
     * This method can be helpful when you need to create an index for renamed table or column
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
        $tableName,
        array $columnNames,
        $indexName = null
    ) {
        if (!$indexName) {
            $indexName = $this->nameGenerator->generateIndexName($tableName, $columnNames, true);
        }
        $index              = new Index($indexName, $columnNames, true);
        $diff               = new TableDiff($tableName);
        $diff->addedIndexes = [$indexName => $index];

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Create a foreign key constraint without check of table and columns existence.
     * This method can be helpful when you need to create a constraint for renamed table or column
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
        $tableName,
        $foreignTable,
        array $localColumnNames,
        array $foreignColumnNames,
        array $options = [],
        $constraintName = null
    ) {
        if (!$constraintName) {
            $constraintName = $this->nameGenerator->generateForeignKeyConstraintName(
                $tableName,
                $localColumnNames
            );
        }
        $constraint             = new ForeignKeyConstraint(
            $localColumnNames,
            $foreignTable,
            $foreignColumnNames,
            $constraintName,
            $options
        );
        $diff                   = new TableDiff($tableName);
        $diff->addedForeignKeys = [$constraintName => $constraint];

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }
}

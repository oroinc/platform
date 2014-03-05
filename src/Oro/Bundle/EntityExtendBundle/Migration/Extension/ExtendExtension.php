<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendExtension
{
    const AUTO_GENERATED_ID_COLUMN_NAME = 'id';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     */
    public function __construct(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
    }

    /**
     * Creates a new "extend" table.
     *
     * @param Schema $schema
     * @param string $tableName
     * @param string $entityName
     * @param array  $options
     * @return Table
     */
    public function createExtendTable(
        Schema $schema,
        $tableName,
        $entityName,
        array $options = []
    ) {
        $table = $schema->createTable($tableName);

        // set options
        if (!isset($options['extend'])) {
            $options['extend'] = [];
        }
        $options['extend']['table']       = $tableName;
        $options['extend']['entity_name'] = $entityName;
        $table->addOption(ExtendColumn::ORO_OPTIONS_NAME, $options);

        // add a primary key
        $table->addColumn(self::AUTO_GENERATED_ID_COLUMN_NAME, 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey([self::AUTO_GENERATED_ID_COLUMN_NAME]);

        return $table;
    }

    /**
     * Adds OptionSet column
     *
     * @param Schema       $schema
     * @param Table|string $table A Table object or table name
     * @param string       $columnName
     * @param array        $options
     */
    public function addOptionSet(
        Schema $schema,
        $table,
        $columnName,
        array $options = []
    ) {
        $this->extendOptionsManager->setColumnOptions(
            $this->getTableName($table),
            $columnName,
            'optionSet',
            $options
        );
    }

    /**
     * Adds one-to-many relation
     *
     * @param Schema       $schema
     * @param Table|string $table                     A Table object or table name
     * @param string       $columnName
     * @param Table|string $targetTable               A Table object or table name
     * @param string[]     $targetTitleColumnNames    Column names are used to show a title of related entity
     * @param string[]     $targetDetailedColumnNames Column names are used to show detailed info about related entity
     * @param string[]     $targetGridColumnNames     Column names are used to show related entity in a grid
     * @param array        $options
     */
    public function addOneToManyRelation(
        Schema $schema,
        $table,
        $columnName,
        $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options
    ) {
        $selfTableName            = $this->getTableName($table);
        $selfTable                = $this->getTable($table, $schema);
        $selfClassName            = $this->getEntityClassByTableName($selfTableName);
        $selfColumnName           = sprintf('%s%s_id', ExtendConfigDumper::DEFAULT_PREFIX, $columnName);
        $selfPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($selfTable);
        $selfPrimaryKeyColumn     = $selfTable->getColumn($selfPrimaryKeyColumnName);

        $targetTableName = $this->getTableName($targetTable);
        if (!($targetTable instanceof Table)) {
            $targetTable = $schema->getTable($targetTable);
        }
        $targetColumnName           = sprintf(
            '%s%s_%s_id',
            ExtendConfigDumper::FIELD_PREFIX,
            strtolower($this->getShortClassName($selfClassName)),
            $columnName
        );
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $targetPrimaryKeyColumn     = $targetTable->getColumn($targetPrimaryKeyColumnName);
        $this->checkColumnsExist($targetTable, $targetTitleColumnNames);
        $this->checkColumnsExist($targetTable, $targetDetailedColumnNames);
        $this->checkColumnsExist($targetTable, $targetGridColumnNames);

        $this->addRelationColumn($selfTable, $selfColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
        $selfTable->addUniqueIndex([$selfColumnName], 'UIDX_' . $selfColumnName);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );

        $this->addRelationColumn($targetTable, $targetColumnName, $selfPrimaryKeyColumn, ['notnull' => false]);
        $targetTable->addIndex([$targetColumnName], 'IDX_' . $targetColumnName);
        $targetTable->addForeignKeyConstraint(
            $selfTable,
            [$targetColumnName],
            [$selfPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );

        $options['_target'] = [
            'table_name' => $targetTableName,
            'columns'    => [
                'title'    => $targetTitleColumnNames,
                'detailed' => $targetDetailedColumnNames,
                'grid'     => $targetGridColumnNames,
            ],
        ];

        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $columnName,
            'oneToMany',
            $options
        );
    }

    /**
     * Adds many-to-many relation
     *
     * @param Schema       $schema
     * @param Table|string $table                     A Table object or table name
     * @param string       $columnName
     * @param Table|string $targetTable               A Table object or table name
     * @param string[]     $targetTitleColumnNames    Column names are used to show a title of related entity
     * @param string[]     $targetDetailedColumnNames Column names are used to show detailed info about related entity
     * @param string[]     $targetGridColumnNames     Column names are used to show related entity in a grid
     * @param array        $options
     */
    public function addManyToManyRelation(
        Schema $schema,
        $table,
        $columnName,
        $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options
    ) {
        $selfTableName            = $this->getTableName($table);
        $selfTable                = $this->getTable($table, $schema);
        $selfClassName            = $this->getEntityClassByTableName($selfTableName);
        $selfColumnName           = sprintf('%s%s_id', ExtendConfigDumper::DEFAULT_PREFIX, $columnName);
        $selfRelationName         = sprintf('%s_id', strtolower($this->getShortClassName($selfClassName)));
        $selfPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($selfTable);
        $selfPrimaryKeyColumn     = $selfTable->getColumn($selfPrimaryKeyColumnName);

        $targetTableName = $this->getTableName($targetTable);
        if (!($targetTable instanceof Table)) {
            $targetTable = $schema->getTable($targetTable);
        }
        $targetClassName            = $this->getEntityClassByTableName($targetTableName);
        $targetRelationName         = sprintf('%s_id', strtolower($this->getShortClassName($targetClassName)));
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $targetPrimaryKeyColumn     = $targetTable->getColumn($targetPrimaryKeyColumnName);
        $this->checkColumnsExist($targetTable, $targetTitleColumnNames);
        $this->checkColumnsExist($targetTable, $targetDetailedColumnNames);
        $this->checkColumnsExist($targetTable, $targetGridColumnNames);

        $this->addRelationColumn($selfTable, $selfColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
        $selfTable->addUniqueIndex([$selfColumnName], 'UIDX_' . $selfColumnName);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );

        $relationsTableName = $this->buildManyToManyRelationTableName($selfClassName, $targetClassName, $columnName);
        $relationsTable     = $schema->createTable($relationsTableName);
        $this->addRelationColumn($relationsTable, $selfRelationName, $selfPrimaryKeyColumn);
        $relationsTable->addIndex([$selfRelationName], 'IDX_' . $selfRelationName);
        $relationsTable->addForeignKeyConstraint(
            $selfTable,
            [$selfRelationName],
            [$selfPrimaryKeyColumnName],
            ['onDelete' => 'CASCADE']
        );
        $this->addRelationColumn($relationsTable, $targetRelationName, $targetPrimaryKeyColumn);
        $relationsTable->addIndex([$targetRelationName], 'IDX_' . $targetRelationName);
        $relationsTable->addForeignKeyConstraint(
            $targetTable,
            [$targetRelationName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'CASCADE']
        );
        $relationsTable->setPrimaryKey([$selfRelationName, $targetRelationName]);

        $options['_target'] = [
            'table_name' => $targetTableName,
            'columns'    => [
                'title'    => $targetTitleColumnNames,
                'detailed' => $targetDetailedColumnNames,
                'grid'     => $targetGridColumnNames,
            ],
        ];

        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $columnName,
            'manyToMany',
            $options
        );
    }

    /**
     * Adds many-to-one relation
     *
     * @param Schema       $schema
     * @param Table|string $table            A Table object or table name
     * @param string       $columnName
     * @param Table|string $targetTable      A Table object or table name
     * @param string       $targetColumnName A column name is used to show related entity
     * @param array        $options
     * @throws \RuntimeException
     */
    public function addManyToOneRelation(
        Schema $schema,
        $table,
        $columnName,
        $targetTable,
        $targetColumnName,
        array $options
    ) {
        $selfTableName  = $this->getTableName($table);
        $selfTable      = $this->getTable($table, $schema);
        $selfColumnName = sprintf('%s%s_id', ExtendConfigDumper::FIELD_PREFIX, $columnName);

        $targetTableName = $this->getTableName($targetTable);
        if (!($targetTable instanceof Table)) {
            $targetTable = $schema->getTable($targetTable);
        }
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $targetPrimaryKeyColumn     = $targetTable->getColumn($targetPrimaryKeyColumnName);
        $this->checkColumnsExist($targetTable, [$targetColumnName]);

        $this->addRelationColumn($selfTable, $selfColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
        $selfTable->addIndex([$selfColumnName], 'IDX_' . $selfColumnName);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );

        $options['_target'] = [
            'table_name' => $targetTableName,
            'column'     => $targetColumnName,
        ];

        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $columnName,
            'manyToOne',
            $options
        );
    }


    /**
     * @param Table|string $table A Table object or table name
     * @return string
     */
    protected function getTableName($table)
    {
        return $table instanceof Table ? $table->getName() : $table;
    }

    /**
     * @param Table|string $table A Table object or table name
     * @param Schema       $schema
     * @return Table
     */
    protected function getTable($table, Schema $schema)
    {
        return $table instanceof Table ? $table : $schema->getTable($table);
    }

    /**
     * Gets an entity full class name by a table name
     *
     * @param string $tableName
     * @return string|null
     */
    protected function getEntityClassByTableName($tableName)
    {
        return $this->extendOptionsManager
            ->getEntityClassResolver()
            ->getEntityClassByTableName($tableName);
    }

    /**
     * @param Table    $table
     * @param string[] $columnNames
     * @throws \InvalidArgumentException if $columnNames array is empty
     * @throws SchemaException if any column is not exist
     */
    protected function checkColumnsExist($table, array $columnNames)
    {
        if (empty($columnNames)) {
            throw new \InvalidArgumentException('At least one column must be specified.');
        }
        foreach ($columnNames as $columnName) {
            $table->getColumn($columnName);
        }
    }

    /**
     * @param Table $table
     * @return string
     * @throws SchemaException if valid primary key does not exist
     */
    protected function getPrimaryKeyColumnName(Table $table)
    {
        if (!$table->hasPrimaryKey()) {
            throw new SchemaException(
                sprintf('The table "%s" must have a primary key.', $table->getName())
            );
        }
        $primaryKeyColumns = $table->getPrimaryKey()->getColumns();
        if (count($primaryKeyColumns) !== 1) {
            throw new SchemaException(
                sprintf('A primary key of "%s" table must include only one column.', $table->getName())
            );
        }

        return array_pop($primaryKeyColumns);
    }

    /**
     * @param Table  $table
     * @param string $columnName
     * @param Column $targetColumn
     * @param array  $options
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addRelationColumn(Table $table, $columnName, Column $targetColumn, array $options = [])
    {
        $columnTypeName = $targetColumn->getType()->getName();
        if (!in_array($columnTypeName, [Type::INTEGER, Type::SMALLINT, Type::BIGINT])) {
            throw new SchemaException(
                sprintf('A relation column type must be an integer. "%s" type is not supported.', $columnTypeName)
            );
        }

        $table->addColumn($columnName, $columnTypeName, $options);
    }

    /**
     * Builds a table name for many-to-many relation
     *
     * @param string $selfClassName
     * @param string $targetClassName
     * @param string $columnName
     * @return string
     */
    protected function buildManyToManyRelationTableName($selfClassName, $targetClassName, $columnName)
    {
        return sprintf(
            'oro_%s_%s_%s',
            strtolower($this->getShortClassName($selfClassName)),
            strtolower($this->getShortClassName($targetClassName)),
            $columnName
        );
    }

    /**
     * @param string $className The full name of a class
     * @return string
     */
    protected function getShortClassName($className)
    {
        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\DatabaseIdentifierNameGenerator;

class ExtendExtension
{
    const AUTO_GENERATED_ID_COLUMN_NAME = 'id';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @var DatabaseIdentifierNameGenerator
     */
    protected $dbIdentifierNameGenerator;

    /**
     * @param ExtendOptionsManager            $extendOptionsManager
     * @param DatabaseIdentifierNameGenerator $dbIdentifierNameGenerator
     */
    public function __construct(
        ExtendOptionsManager $extendOptionsManager,
        DatabaseIdentifierNameGenerator $dbIdentifierNameGenerator
    ) {
        $this->extendOptionsManager      = $extendOptionsManager;
        $this->dbIdentifierNameGenerator = $dbIdentifierNameGenerator;
    }

    /**
     * Creates a table for a custom entity.
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param Schema $schema
     * @param string $tableName
     * @param string $entityName
     * @param array  $options
     * @return Table
     * @throws \InvalidArgumentException
     */
    public function createCustomEntityTable(
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
        if (empty($entityName) || !preg_match('/^[A-Z][a-zA-Z\d]+$/', $entityName)) {
            throw new \InvalidArgumentException(sprintf('Invalid entity name: "%s".', $entityName));
        }
        $options['_entity_name']    = ExtendConfigDumper::ENTITY . $entityName;
        $options['extend']['table'] = $tableName;
        if (isset($options['extend']['owner'])) {
            if ($options['extend']['owner'] !== ExtendScope::OWNER_CUSTOM) {
                throw new \InvalidArgumentException(
                    sprintf('The "extend.owner" option for a custom entity must be "%s".', ExtendScope::OWNER_CUSTOM)
                );
            }
        } else {
            $options['extend']['owner'] = ExtendScope::OWNER_CUSTOM;
        }
        if (isset($options['extend']['is_extend'])) {
            if ($options['extend']['is_extend'] !== true) {
                throw new \InvalidArgumentException(
                    'The "extend.is_extend" option for a custom entity must be TRUE.'
                );
            }
        } else {
            $options['extend']['is_extend'] = true;
        }
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
        array $options = []
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
        $selfTable->addUniqueIndex(
            [$selfColumnName],
            $this->dbIdentifierNameGenerator->generateIndexName($selfTableName, [$selfColumnName], true)
        );
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL'],
            $this->dbIdentifierNameGenerator->generateForeignKeyConstraintName(
                $selfTableName,
                [$selfColumnName],
                $targetTableName,
                [$targetPrimaryKeyColumnName]
            )
        );

        $this->addRelationColumn($targetTable, $targetColumnName, $selfPrimaryKeyColumn, ['notnull' => false]);
        $targetTable->addIndex(
            [$targetColumnName],
            $this->dbIdentifierNameGenerator->generateIndexName($targetTableName, [$targetColumnName])
        );
        $targetTable->addForeignKeyConstraint(
            $selfTable,
            [$targetColumnName],
            [$selfPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL'],
            $this->dbIdentifierNameGenerator->generateForeignKeyConstraintName(
                $targetTableName,
                [$targetColumnName],
                $selfTableName,
                [$selfPrimaryKeyColumnName]
            )
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function addManyToManyRelation(
        Schema $schema,
        $table,
        $columnName,
        $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options = []
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
        $selfTable->addUniqueIndex(
            [$selfColumnName],
            $this->dbIdentifierNameGenerator->generateIndexName($selfTableName, [$selfColumnName], true)
        );
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL'],
            $this->dbIdentifierNameGenerator->generateForeignKeyConstraintName(
                $selfTableName,
                [$selfColumnName],
                $targetTableName,
                [$targetPrimaryKeyColumnName]
            )
        );

        $relationsTableName = $this->dbIdentifierNameGenerator->generateManyToManyJoinTableName(
            $selfClassName,
            $columnName,
            $targetClassName
        );
        $relationsTable     = $schema->createTable($relationsTableName);
        $this->addRelationColumn($relationsTable, $selfRelationName, $selfPrimaryKeyColumn);
        $relationsTable->addIndex(
            [$selfRelationName],
            $this->dbIdentifierNameGenerator->generateIndexName($relationsTableName, [$selfRelationName])
        );
        $relationsTable->addForeignKeyConstraint(
            $selfTable,
            [$selfRelationName],
            [$selfPrimaryKeyColumnName],
            ['onDelete' => 'CASCADE'],
            $this->dbIdentifierNameGenerator->generateForeignKeyConstraintName(
                $relationsTableName,
                [$selfRelationName],
                $selfTableName,
                [$selfPrimaryKeyColumnName]
            )
        );
        $this->addRelationColumn($relationsTable, $targetRelationName, $targetPrimaryKeyColumn);
        $relationsTable->addIndex(
            [$targetRelationName],
            $this->dbIdentifierNameGenerator->generateIndexName($relationsTableName, [$targetRelationName])
        );
        $relationsTable->addForeignKeyConstraint(
            $targetTable,
            [$targetRelationName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'CASCADE'],
            $this->dbIdentifierNameGenerator->generateForeignKeyConstraintName(
                $relationsTableName,
                [$targetRelationName],
                $targetTableName,
                [$targetPrimaryKeyColumnName]
            )
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
        array $options = []
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
        $selfTable->addIndex(
            [$selfColumnName],
            $this->dbIdentifierNameGenerator->generateIndexName($selfTableName, [$selfColumnName])
        );
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL'],
            $this->dbIdentifierNameGenerator->generateForeignKeyConstraintName(
                $selfTableName,
                [$selfColumnName],
                $targetTableName,
                [$targetPrimaryKeyColumnName]
            )
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
     * @param string $className The full name of a class
     * @return string
     */
    protected function getShortClassName($className)
    {
        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}

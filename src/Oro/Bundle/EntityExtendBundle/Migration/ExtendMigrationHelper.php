<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Extend\Schema\ExtendColumn;
use Oro\Bundle\EntityExtendBundle\Extend\Schema\ExtendOptionManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendMigrationHelper
{
    const AUTO_GENERATED_ID_COLUMN_NAME = 'id';

    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @param ExtendOptionManager $extendOptionManager
     */
    public function __construct(ExtendOptionManager $extendOptionManager)
    {
        $this->extendOptionManager = $extendOptionManager;
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
        $this->extendOptionManager->setColumnOptions(
            $table instanceof Table ? $table->getName() : $table,
            $columnName,
            'optionSet',
            $options
        );
    }

    /**
     * Adds one-to-many relation
     *
     * @param Schema       $schema
     * @param Table|string $table A Table object or table name
     * @param string       $columnName
     * @param array        $options
     */
    public function addRelationOneToMany(
        Schema $schema,
        $table,
        $columnName,
        array $options
    ) {
        $selfColumnName = sprintf('%s%s_id', ExtendConfigDumper::DEFAULT_PREFIX, $columnName);
        $selfTableName  = $table instanceof Table ? $table->getName() : $table;
        $selfTable      = $table instanceof Table ? $table : $schema->getTable($table);
        $selfClassName  = $this->getEntityClassByTableName($selfTableName);

        $targetTableName  = $options['extend']['target']['table_name'];
        $targetTable      = $schema->getTable($targetTableName);
        $targetColumnName = sprintf(
            '%s%s_%s_id',
            ExtendConfigDumper::FIELD_PREFIX,
            strtolower(array_pop(explode('\\', $selfClassName))),
            $columnName
        );

        $selfTable->addColumn($selfColumnName, 'integer', ['notnull' => false]);
        $selfTable->addUniqueIndex([$selfColumnName], 'UNIQUE_' . $selfColumnName);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            $targetTable->getPrimaryKey()->getColumns(),
            ['onDelete' => 'SET NULL']
        );

        $targetTable->addColumn($targetColumnName, 'integer', ['notnull' => false]);
        $targetTable->addIndex([$targetColumnName], 'IDX_' . $targetColumnName);
        $targetTable->addForeignKeyConstraint(
            $selfTable,
            [$targetColumnName],
            $selfTable->getPrimaryKey()->getColumns(),
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Adds many-to-many relation
     *
     * @param Schema       $schema
     * @param Table|string $table A Table object or table name
     * @param string       $columnName
     * @param array        $options
     */
    public function addRelationManyToMany(
        Schema $schema,
        $table,
        $columnName,
        array $options
    ) {
        $selfColumnName   = sprintf('%s%s_id', ExtendConfigDumper::DEFAULT_PREFIX, $columnName);
        $selfTableName    = $table instanceof Table ? $table->getName() : $table;
        $selfTable        = $table instanceof Table ? $table : $schema->getTable($table);
        $selfClassName    = $this->getEntityClassByTableName($selfTableName);
        $selfRelationName = sprintf('%s_id', strtolower(array_pop(explode('\\', $selfClassName))));

        $targetTableName    = $options['extend']['target']['table_name'];
        $targetTable        = $schema->getTable($targetTableName);
        $targetClassName    = $this->getEntityClassByTableName($targetTableName);
        $targetRelationName = sprintf('%s_id', strtolower(array_pop(explode('\\', $targetClassName))));

        $selfTable->addColumn($selfColumnName, 'integer', ['notnull' => false]);
        $selfTable->addUniqueIndex([$selfColumnName], 'UNIQ_' . $selfColumnName);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            $targetTable->getPrimaryKey()->getColumns(),
            ['onDelete' => 'SET NULL']
        );

        $relationsTableName = $this->buildManyToManyRelationTableName($selfClassName, $targetClassName, $columnName);
        $relationsTable     = $schema->createTable($relationsTableName);
        $relationsTable->addColumn($selfRelationName, 'integer');
        $relationsTable->addIndex([$selfRelationName], 'IDX_' . $selfRelationName);
        $relationsTable->addForeignKeyConstraint(
            $selfTable,
            [$selfRelationName],
            $selfTable->getPrimaryKey()->getColumns(),
            ['onDelete' => 'CASCADE']
        );
        $relationsTable->addColumn($targetRelationName, 'integer');
        $relationsTable->addIndex([$targetRelationName], 'IDX_' . $targetRelationName);
        $relationsTable->addForeignKeyConstraint(
            $targetTable,
            [$targetRelationName],
            $targetTable->getPrimaryKey()->getColumns(),
            ['onDelete' => 'CASCADE']
        );
        $relationsTable->setPrimaryKey([$selfRelationName, $targetRelationName]);
    }

    /**
     * Adds many-to-one relation
     *
     * @param Schema       $schema
     * @param Table|string $table A Table object or table name
     * @param string       $columnName
     * @param array        $options
     */
    public function addRelationManyToOne(
        Schema $schema,
        $table,
        $columnName,
        array $options
    ) {
        $selfColumnName  = sprintf('%s%s_id', ExtendConfigDumper::FIELD_PREFIX, $columnName);
        $selfTable       = $table instanceof Table ? $table : $schema->getTable($table);
        $targetTableName = $options['extend']['target']['table_name'];
        $targetTable     = $schema->getTable($targetTableName);

        $selfTable->addColumn($selfColumnName, 'integer', ['notnull' => false]);
        $selfTable->addIndex([$selfColumnName], 'IDX_' . $selfColumnName);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            $targetTable->getPrimaryKey()->getColumns(),
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Gets an entity full class name by a table name
     *
     * @param string $tableName
     * @return string|null
     */
    protected function getEntityClassByTableName($tableName)
    {
        return $this->extendOptionManager
            ->getEntityClassResolver()
            ->getEntityClassByTableName($tableName);
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
            strtolower(array_pop(explode('\\', $selfClassName))),
            strtolower(array_pop(explode('\\', $targetClassName))),
            $columnName
        );
    }
}

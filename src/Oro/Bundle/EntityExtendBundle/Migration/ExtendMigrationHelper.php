<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Extend\Schema\ExtendOptionManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendMigrationHelper
{
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
     * Adds OptionSet column
     *
     * @param Schema $schema
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function addOptionSet(
        Schema $schema,
        $tableName,
        $columnName,
        array $options = []
    ) {
        $this->extendOptionManager->addColumnOptions(
            $tableName,
            $columnName,
            'optionSet',
            $options
        );
    }

    /**
     * Adds one-to-many relation
     *
     * @param Schema $schema
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function addRelationOneToMany(
        Schema $schema,
        $tableName,
        $columnName,
        array $options
    ) {
        $selfColumnName = sprintf('%s%s_id', ExtendConfigDumper::DEFAULT_PREFIX, $columnName);
        $selfTable      = $schema->getTable($tableName);
        $selfClassName  = $this->getEntityClassByTableName($tableName);

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
     * @param Schema $schema
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function addRelationManyToMany(
        Schema $schema,
        $tableName,
        $columnName,
        array $options
    ) {
        $selfColumnName   = sprintf('%s%s_id', ExtendConfigDumper::DEFAULT_PREFIX, $columnName);
        $selfTable        = $schema->getTable($tableName);
        $selfClassName    = $this->getEntityClassByTableName($tableName);
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
     * @param Schema $schema
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function addRelationManyToOne(
        Schema $schema,
        $tableName,
        $columnName,
        array $options
    ) {
        $selfColumnName  = sprintf('%s%s_id', ExtendConfigDumper::FIELD_PREFIX, $columnName);
        $selfTable       = $schema->getTable($tableName);
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

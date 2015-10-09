<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;

class ExtendExtension implements NameGeneratorAwareInterface
{
    const AUTO_GENERATED_ID_COLUMN_NAME = 'id';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     * @param EntityMetadataHelper $entityMetadataHelper
     */
    public function __construct(
        ExtendOptionsManager $extendOptionsManager,
        EntityMetadataHelper $entityMetadataHelper
    ) {
        $this->extendOptionsManager = $extendOptionsManager;
        $this->entityMetadataHelper = $entityMetadataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * @return ExtendDbIdentifierNameGenerator
     */
    public function getNameGenerator()
    {
        return $this->nameGenerator;
    }

    /**
     * Creates a table for a custom entity.
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param Schema $schema
     * @param string $entityName
     * @param array  $options
     *
     * @return Table
     *
     * @throws \InvalidArgumentException
     */
    public function createCustomEntityTable(
        Schema $schema,
        $entityName,
        array $options = []
    ) {
        $className = ExtendHelper::ENTITY_NAMESPACE . $entityName;
        $tableName = $this->nameGenerator->generateCustomEntityTableName($className);
        $table     = $schema->createTable($tableName);
        $this->entityMetadataHelper->registerEntityClass($tableName, $className);

        $options = new OroOptions($options);
        // set options
        $options->setAuxiliary(ExtendOptionsManager::ENTITY_CLASS_OPTION, $className);
        if ($options->has('extend', 'owner')) {
            if ($options->get('extend', 'owner') !== ExtendScope::OWNER_CUSTOM) {
                throw new \InvalidArgumentException(
                    sprintf('The "extend.owner" option for a custom entity must be "%s".', ExtendScope::OWNER_CUSTOM)
                );
            }
        } else {
            $options->set('extend', 'owner', ExtendScope::OWNER_CUSTOM);
        }
        if ($options->has('extend', 'is_extend')) {
            if ($options->get('extend', 'is_extend') !== true) {
                throw new \InvalidArgumentException(
                    'The "extend.is_extend" option for a custom entity must be TRUE.'
                );
            }
        } else {
            $options->set('extend', 'is_extend', true);
        }
        $table->addOption(OroOptions::KEY, $options);

        // add a primary key
        $table->addColumn(self::AUTO_GENERATED_ID_COLUMN_NAME, 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey([self::AUTO_GENERATED_ID_COLUMN_NAME]);

        return $table;
    }

    /**
     * Creates a table that is used to store enum values for the enum with the given code.
     *
     * @param Schema        $schema
     * @param string        $enumCode   The unique identifier of an enum
     * @param bool          $isMultiple Indicates whether several options can be selected for this enum
     *                                  or it supports only one selected option
     * @param bool          $isPublic   Indicates whether this enum can be used by any entity or
     *                                  it is designed to use in one entity only
     * @param bool|string[] $immutable  Indicates whether the changing the list of enum values and
     *                                  public flag is allowed or not. More details can be found
     *                                  in entity_config.yml
     * @param array         $options
     *
     * @return Table A table that is used to store enum values
     *
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createEnum(
        Schema $schema,
        $enumCode,
        $isMultiple = false,
        $isPublic = false,
        $immutable = false,
        array $options = []
    ) {
        if ($enumCode !== ExtendHelper::buildEnumCode($enumCode)) {
            new \InvalidArgumentException(
                sprintf(
                    'The enum code "%s" must contain only lower alphabetical symbols, numbers and underscore.',
                    $enumCode
                )
            );
        }

        $tableName = $this->nameGenerator->generateEnumTableName($enumCode);
        $className = ExtendHelper::buildEnumValueClassName($enumCode);

        $options = array_replace_recursive(
            [
                ExtendOptionsManager::MODE_OPTION         => ConfigModel::MODE_HIDDEN,
                ExtendOptionsManager::ENTITY_CLASS_OPTION => $className,
                'entity'                                  => [
                    'label'        => ExtendHelper::getEnumTranslationKey('label', $enumCode),
                    'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                    'description'  => ExtendHelper::getEnumTranslationKey('description', $enumCode)
                ],
                'extend'                                  => [
                    'owner'     => ExtendScope::OWNER_SYSTEM,
                    'is_extend' => true,
                    'table'     => $tableName,
                    'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS
                ],
                'enum'                                    => [
                    'code'     => $enumCode,
                    'public'   => $isPublic,
                    'multiple' => $isMultiple
                ]
            ],
            $options
        );
        if ($immutable) {
            $options['enum']['immutable'] = true;
        }

        $table = $schema->createTable($tableName);
        $this->entityMetadataHelper->registerEntityClass($tableName, $className);
        $table->addOption(OroOptions::KEY, $options);

        $table->addColumn(
            'id',
            'string',
            [
                'length'        => ExtendHelper::MAX_ENUM_VALUE_ID_LENGTH,
                OroOptions::KEY => [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'id'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'id')
                    ],
                    'importexport' => [
                        'identity' => true
                    ]
                ]
            ]
        );
        $table->addColumn(
            'name',
            'string',
            [
                'length'        => 255,
                OroOptions::KEY => [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'name'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'name')
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ]
                ]
            ]
        );
        $table->addColumn(
            'priority',
            'integer',
            [
                OroOptions::KEY => [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'priority'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'priority')
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ]
                ]
            ]
        );
        $table->addColumn(
            'is_default',
            'boolean',
            [
                OroOptions::KEY => [
                    ExtendOptionsManager::FIELD_NAME_OPTION => 'default',
                    'entity'                                => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'default'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'default')
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ]
                ]
            ]
        );
        $table->setPrimaryKey(['id']);

        return $table;
    }

    /**
     * Adds enumerable field
     *
     * Take in attention that this method creates new private enum if the enum with the given code
     * is not exist yet. If you want to create a public enum use {@link createEnum} method before.
     *
     * @param Schema        $schema
     * @param Table|string  $table           A Table object or table name
     * @param string        $associationName A relation name
     * @param string        $enumCode        The target enum identifier
     * @param bool          $isMultiple      Indicates whether several options can be selected for this enum
     *                                       or it supports only one selected option
     * @param bool|string[] $immutable       Indicates whether the changing the list of enum values and
     *                                       public flag is allowed or not. More details can be found
     *                                       in entity_config.yml
     * @param array         $options
     *
     * @return Table A table that is used to store enum values
     */
    public function addEnumField(
        Schema $schema,
        $table,
        $associationName,
        $enumCode,
        $isMultiple = false,
        $immutable = false,
        array $options = []
    ) {
        $enumTableName = $this->nameGenerator->generateEnumTableName($enumCode);
        $selfTable     = $this->getTable($table, $schema);
        $enumTable     = null;

        // make sure a table that is used to store enum values exists
        if (!$schema->hasTable($enumTableName)) {
            $enumTable = $this->createEnum($schema, $enumCode, $isMultiple, false, $immutable);
        } else {
            $enumTable = $this->getTable($enumTableName, $schema);
        }

        // create appropriate relation
        $options['enum']['enum_code'] = $enumCode;
        if ($isMultiple) {
            $options['extend']['without_default'] = true;
            $this->addManyToManyRelation(
                $schema,
                $selfTable,
                $associationName,
                $enumTableName,
                ['name'],
                ['name'],
                ['name'],
                $options,
                'multiEnum'
            );
            // create a column that will contain selected options
            // this column is required to avoid group by clause when multiple enum is shown in a datagrid
            $selfTable->addColumn(
                $this->nameGenerator->generateMultiEnumSnapshotColumnName($associationName),
                'string',
                [
                    'notnull' => false,
                    'length'  => ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH
                ]
            );
        } else {
            $this->addManyToOneRelation(
                $schema,
                $selfTable,
                $associationName,
                $enumTableName,
                'name',
                $options,
                'enum'
            );
        }

        return $enumTable;
    }

    /**
     * Adds one-to-many relation
     *
     * @param Schema       $schema
     * @param Table|string $table                     A Table object or table name
     * @param string       $associationName           A relation name
     * @param Table|string $targetTable               A Table object or table name
     * @param string[]     $targetTitleColumnNames    Column names are used to show a title of related entity
     * @param string[]     $targetDetailedColumnNames Column names are used to show detailed info about related entity
     * @param string[]     $targetGridColumnNames     Column names are used to show related entity in a grid
     * @param array        $options                   Entity config values
     *                                                format is [CONFIG_SCOPE => [CONFIG_KEY => CONFIG_VALUE]]
     * @param string       $fieldType                 The field type. By default the field type is oneToMany,
     *                                                but you can specify another type if it is based on oneToMany.
     *                                                In this case this type should be registered
     *                                                in entity_extend.yml under underlying_types section
     */
    public function addOneToManyRelation(
        Schema $schema,
        $table,
        $associationName,
        $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options = [],
        $fieldType = RelationType::ONE_TO_MANY
    ) {
        $this->ensureExtendFieldSet($options);

        $selfTableName            = $this->getTableName($table);
        $selfTable                = $this->getTable($table, $schema);
        $selfClassName            = $this->getEntityClassByTableName($selfTableName);
        $selfPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($selfTable);
        $selfPrimaryKeyColumn     = $selfTable->getColumn($selfPrimaryKeyColumnName);

        $targetTableName            = $this->getTableName($targetTable);
        $targetTable                = $this->getTable($targetTable, $schema);
        $targetColumnName           = $this->nameGenerator
            ->generateOneToManyRelationColumnName($selfClassName, $associationName);
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $this->checkColumnsExist($targetTable, $targetTitleColumnNames);
        $this->checkColumnsExist($targetTable, $targetDetailedColumnNames);
        $this->checkColumnsExist($targetTable, $targetGridColumnNames);

        if (!isset($options['extend']['without_default']) || !$options['extend']['without_default']) {
            $selfColumnName         = $this->nameGenerator->generateRelationDefaultColumnName($associationName);
            $targetPrimaryKeyColumn = $targetTable->getColumn($targetPrimaryKeyColumnName);
            $this->addRelationColumn($selfTable, $selfColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
            $selfTable->addUniqueIndex([$selfColumnName]);
            $selfTable->addForeignKeyConstraint(
                $targetTable,
                [$selfColumnName],
                [$targetPrimaryKeyColumnName],
                ['onDelete' => 'SET NULL']
            );
        }

        $this->addRelationColumn($targetTable, $targetColumnName, $selfPrimaryKeyColumn, ['notnull' => false]);
        $targetTable->addIndex([$targetColumnName]);
        $targetTable->addForeignKeyConstraint(
            $selfTable,
            [$targetColumnName],
            [$selfPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'columns'    => [
                'title'    => $targetTitleColumnNames,
                'detailed' => $targetDetailedColumnNames,
                'grid'     => $targetGridColumnNames,
            ],
        ];

        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;
        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $options
        );
    }

    /**
     * Adds many-to-many relation
     *
     * @param Schema       $schema
     * @param Table|string $table                     A Table object or table name
     * @param string       $associationName           A relation name
     * @param Table|string $targetTable               A Table object or table name
     * @param string[]     $targetTitleColumnNames    Column names are used to show a title of related entity
     * @param string[]     $targetDetailedColumnNames Column names are used to show detailed info about related entity
     * @param string[]     $targetGridColumnNames     Column names are used to show related entity in a grid
     * @param array        $options
     * @param string       $fieldType                 The field type. By default the field type is manyToMany,
     *                                                but you can specify another type if it is based on manyToMany.
     *                                                In this case this type should be registered
     *                                                in entity_extend.yml under underlying_types section
     */
    public function addManyToManyRelation(
        Schema $schema,
        $table,
        $associationName,
        $targetTable,
        array $targetTitleColumnNames,
        array $targetDetailedColumnNames,
        array $targetGridColumnNames,
        array $options = [],
        $fieldType = RelationType::MANY_TO_MANY
    ) {
        $this->ensureExtendFieldSet($options);

        $selfTableName            = $this->getTableName($table);
        $selfTable                = $this->getTable($table, $schema);
        $selfClassName            = $this->getEntityClassByTableName($selfTableName);
        $selfRelationName         = $this->nameGenerator->generateManyToManyRelationColumnName($selfClassName);
        $selfPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($selfTable);
        $selfPrimaryKeyColumn     = $selfTable->getColumn($selfPrimaryKeyColumnName);

        $targetTableName            = $this->getTableName($targetTable);
        $targetTable                = $this->getTable($targetTable, $schema);
        $targetClassName            = $this->getEntityClassByTableName($targetTableName);
        $targetRelationName         = $this->nameGenerator->generateManyToManyRelationColumnName($targetClassName);
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $targetPrimaryKeyColumn     = $targetTable->getColumn($targetPrimaryKeyColumnName);

        $this->checkColumnsExist($targetTable, $targetTitleColumnNames);
        $this->checkColumnsExist($targetTable, $targetDetailedColumnNames);
        $this->checkColumnsExist($targetTable, $targetGridColumnNames);

        if (!isset($options['extend']['without_default']) || !$options['extend']['without_default']) {
            $selfColumnName = $this->nameGenerator->generateRelationDefaultColumnName($associationName);
            $this->addRelationColumn($selfTable, $selfColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
            $selfTable->addUniqueIndex([$selfColumnName]);
            $selfTable->addForeignKeyConstraint(
                $targetTable,
                [$selfColumnName],
                [$targetPrimaryKeyColumnName],
                ['onDelete' => 'SET NULL']
            );
        }

        $relationsTableName = $this->nameGenerator->generateManyToManyJoinTableName(
            $selfClassName,
            $associationName,
            $targetClassName
        );
        $relationsTable     = $schema->createTable($relationsTableName);
        $this->addRelationColumn($relationsTable, $selfRelationName, $selfPrimaryKeyColumn);
        $relationsTable->addIndex([$selfRelationName]);
        $relationsTable->addForeignKeyConstraint(
            $selfTable,
            [$selfRelationName],
            [$selfPrimaryKeyColumnName],
            ['onDelete' => 'CASCADE']
        );
        $this->addRelationColumn($relationsTable, $targetRelationName, $targetPrimaryKeyColumn);
        $relationsTable->addIndex([$targetRelationName]);
        $relationsTable->addForeignKeyConstraint(
            $targetTable,
            [$targetRelationName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'CASCADE']
        );
        $relationsTable->setPrimaryKey([$selfRelationName, $targetRelationName]);

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'columns'    => [
                'title'    => $targetTitleColumnNames,
                'detailed' => $targetDetailedColumnNames,
                'grid'     => $targetGridColumnNames,
            ],
        ];

        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;
        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $options
        );
    }

    /**
     * Adds many-to-one relation
     *
     * @param Schema       $schema
     * @param Table|string $table            A Table object or table name
     * @param string       $associationName  A relation name
     * @param Table|string $targetTable      A Table object or table name
     * @param string       $targetColumnName A column name is used to show related entity
     * @param array        $options
     * @param string       $fieldType        The field type. By default the field type is manyToOne,
     *                                       but you can specify another type if it is based on manyToOne.
     *                                       In this case this type should be registered
     *                                       in entity_extend.yml under underlying_types section
     */
    public function addManyToOneRelation(
        Schema $schema,
        $table,
        $associationName,
        $targetTable,
        $targetColumnName,
        array $options = [],
        $fieldType = RelationType::MANY_TO_ONE
    ) {
        $this->ensureExtendFieldSet($options);

        $selfTableName  = $this->getTableName($table);
        $selfTable      = $this->getTable($table, $schema);
        $selfColumnName = $this->nameGenerator->generateManyToOneRelationColumnName($associationName);

        $targetTableName            = $this->getTableName($targetTable);
        $targetTable                = $this->getTable($targetTable, $schema);
        $targetPrimaryKeyColumnName = $this->getPrimaryKeyColumnName($targetTable);
        $targetPrimaryKeyColumn     = $targetTable->getColumn($targetPrimaryKeyColumnName);
        $this->checkColumnsExist($targetTable, [$targetColumnName]);

        $this->addRelationColumn($selfTable, $selfColumnName, $targetPrimaryKeyColumn, ['notnull' => false]);
        $selfTable->addIndex([$selfColumnName]);
        $selfTable->addForeignKeyConstraint(
            $targetTable,
            [$selfColumnName],
            [$targetPrimaryKeyColumnName],
            ['onDelete' => 'SET NULL']
        );

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'column'     => $targetColumnName,
        ];

        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;
        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $options
        );
    }

    /**
     * Gets an entity full class name by a table name
     *
     * @param string $tableName
     *
     * @return string|null
     */
    public function getEntityClassByTableName($tableName)
    {
        return $this->entityMetadataHelper->getEntityClassByTableName($tableName);
    }

    /**
     * Gets a table name by entity full class name
     *
     * @param string $className
     *
     * @return string|null
     */
    public function getTableNameByEntityClass($className)
    {
        return $this->entityMetadataHelper->getTableNameByEntityClass($className);
    }

    /**
     * Gets a field name by a table name and a column name
     *
     * @param string $tableName
     * @param string $columnName
     *
     * @return string|null
     */
    public function getFieldNameByColumnName($tableName, $columnName)
    {
        return $this->entityMetadataHelper->getFieldNameByColumnName($tableName, $columnName);
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
        if ($targetColumn->getName() !== 'id') {
            throw new SchemaException(
                sprintf(
                    'The target column name must be "id". Relation column: "%s::%s". Target column name: "%s".',
                    $table->getName(),
                    $columnName,
                    $targetColumn->getName()
                )
            );
        }
        $columnTypeName = $targetColumn->getType()->getName();
        if (!in_array($columnTypeName, [Type::INTEGER, Type::STRING, Type::SMALLINT, Type::BIGINT])) {
            throw new SchemaException(
                sprintf(
                    'The type of relation column "%s::%s" must be an integer or string. "%s" type is not supported.',
                    $table->getName(),
                    $columnName,
                    $columnTypeName
                )
            );
        }

        if ($columnTypeName === Type::STRING && $targetColumn->getLength() !== null) {
            $options['length'] = $targetColumn->getLength();
        }

        $table->addColumn($columnName, $columnTypeName, $options);
    }

    /**
     * Makes sure that required for any extend field attributes are set
     *
     * @param array $options
     */
    protected function ensureExtendFieldSet(array &$options)
    {
        if (!isset($options['extend'])) {
            $options['extend'] = [];
        }
        if (!isset($options['extend']['is_extend'])) {
            $options['extend']['is_extend'] = true;
        }
        if (!isset($options['extend']['owner'])) {
            $options['extend']['owner'] = ExtendScope::OWNER_SYSTEM;
        }
    }
}

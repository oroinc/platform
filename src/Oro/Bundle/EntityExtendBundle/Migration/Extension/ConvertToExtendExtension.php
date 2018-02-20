<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ConvertToExtendExtension
{
    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var ConfigModelManager */
    protected $modelManager;

    /** @var array */
    protected $scopes = ['entity', 'extend', 'form', 'view', 'merge', 'dataaudit'];

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param ConfigModelManager $modelManager
     */
    public function __construct(
        ExtendOptionsManager $extendOptionsManager,
        EntityMetadataHelper $entityMetadataHelper,
        ConfigModelManager $modelManager
    ) {
        $this->extendOptionsManager = $extendOptionsManager;
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->modelManager = $modelManager;
    }

    /**
     * Adds many-to-one relation
     *
     * @param QueryBag     $queries
     * @param Schema       $schema
     * @param string       $currentEntityName
     * @param string       $currentAssociationName  field name which should be deleted
     * @param Table|string $table                   A Table object or table name
     * @param string       $associationName         The name of a relation field
     * @param Table|string $targetTable             A Table object or table name
     * @param string       $targetColumnName        A column name is used to show related entity
     * @param array        $options
     */
    public function manyToOneRelation(
        QueryBag $queries,
        Schema $schema,
        $currentEntityName,
        $currentAssociationName,
        $table,
        $associationName,
        $targetTable,
        $targetColumnName,
        array $options = []
    ) {
        $currentOptions = $this->getOptionForCurrentField($currentEntityName, $currentAssociationName);
        $this->ensureExtendFieldSet($options);

        $selfTableName        = $this->getTableName($table);
        $targetTableName      = $this->getTableName($targetTable);
        $targetTable          = $this->getTable($targetTable, $schema);

        $this->checkColumnsExist($targetTable, [$targetColumnName]);

        $options[ExtendOptionsManager::TARGET_OPTION] = [
            'table_name' => $targetTableName,
            'column'     => $targetColumnName,
        ];
        $options[ExtendOptionsManager::TYPE_OPTION]   = RelationType::MANY_TO_ONE;

        $newOptions = array_replace_recursive($currentOptions, $options);

        $this->extendOptionsManager->setColumnOptions(
            $selfTableName,
            $associationName,
            $newOptions
        );

        if ($currentEntityName && $currentAssociationName && $currentAssociationName !== $associationName) {
            $queries->addQuery(
                new RemoveFieldQuery(
                    $currentEntityName,
                    $currentAssociationName
                )
            );
        }
    }

    /**
     * @param string $currentEntityName
     * @param string $currentAssociationName
     *
     * @return array
     */
    protected function getOptionForCurrentField($currentEntityName, $currentAssociationName)
    {
        $currentOptions = [];
        if ($currentEntityName && $currentAssociationName) {
            $model = $this->modelManager->getFieldModel($currentEntityName, $currentAssociationName);

            foreach ($this->scopes as $scope) {
                $data = $model->toArray($scope);
                if (count($data) > 0) {
                    $currentOptions[$scope] = $data;
                }
            }
        }

        return $currentOptions;
    }

    /**
     * @param Table|string $table A Table object or table name
     *
     * @return string
     */
    protected function getTableName($table)
    {
        return $table instanceof Table ? $table->getName() : $table;
    }

    /**
     * @param Table|string $table A Table object or table name
     * @param Schema       $schema
     *
     * @return Table
     */
    protected function getTable($table, Schema $schema)
    {
        return $table instanceof Table ? $table : $schema->getTable($table);
    }

    /**
     * @param Table    $table
     * @param string[] $columnNames
     *
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
            $options['extend']['owner'] = ExtendScope::OWNER_CUSTOM;
        }
    }
}

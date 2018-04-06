<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension as BaseRenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameExtension extends BaseRenameExtension
{
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
     * {@inheritdoc}
     */
    public function renameTable(Schema $schema, QueryBag $queries, $oldTableName, $newTableName)
    {
        $extendOptions = $this->extendOptionsManager->getExtendOptions();
        foreach ($extendOptions as $name => $options) {
            if (strpos($name, '!') === false) {
                // handle table name
                if ($name === $oldTableName) {
                    $this->extendOptionsManager->removeTableOptions($oldTableName);
                    $this->extendOptionsManager->setTableOptions($newTableName, $options);
                }
            } else {
                // handle field name
                list($tableName, $columnName) = explode('!', $name, 2);

                // replace table name in combined field name column
                if ($tableName === $oldTableName) {
                    $tableName = $newTableName;
                    $this->extendOptionsManager->removeColumnOptions($oldTableName, $columnName);
                    $this->extendOptionsManager->setColumnOptions($newTableName, $columnName, $options);
                }

                // replace table name in target section
                if (!empty($options['_target']['table_name']) && $options['_target']['table_name'] === $oldTableName) {
                    $options['_target']['table_name'] = $newTableName;
                    $this->extendOptionsManager->setColumnOptions($tableName, $columnName, $options);
                }
            }
        }

        parent::renameTable($schema, $queries, $oldTableName, $newTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function renameColumn(Schema $schema, QueryBag $queries, Table $table, $oldColumnName, $newColumnName)
    {
        $this->extendOptionsManager->setColumnOptions(
            $table->getName(),
            $oldColumnName,
            [
                ExtendOptionsManager::NEW_NAME_OPTION => $newColumnName
            ]
        );
        parent::renameColumn($schema, $queries, $table, $oldColumnName, $newColumnName);
    }
}

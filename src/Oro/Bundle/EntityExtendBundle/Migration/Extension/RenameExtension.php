<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension as BaseRenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds extended entity specifics to migration extension that is used to rename database tables and columns.
 */
class RenameExtension extends BaseRenameExtension
{
    private ExtendOptionsManager $extendOptionsManager;

    public function __construct(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
    }

    /**
     * {@inheritDoc}
     */
    public function renameTable(Schema $schema, QueryBag $queries, string $oldTableName, string $newTableName): void
    {
        $extendOptions = $this->extendOptionsManager->getExtendOptions();
        foreach ($extendOptions as $name => $options) {
            if (!str_contains($name, '!')) {
                // handle table name
                if ($name === $oldTableName) {
                    $this->extendOptionsManager->removeTableOptions($oldTableName);
                    $this->extendOptionsManager->setTableOptions($newTableName, $options);
                }
            } else {
                // handle field name
                [$tableName, $columnName] = explode('!', $name, 2);

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
     * {@inheritDoc}
     */
    public function renameColumn(
        Schema $schema,
        QueryBag $queries,
        Table $table,
        string $oldColumnName,
        string $newColumnName
    ): void {
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

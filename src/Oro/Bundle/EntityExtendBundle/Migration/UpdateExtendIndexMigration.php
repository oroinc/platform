<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateExtendIndexMigration implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param Connection $connection
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        Connection $connection
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->connection           = $connection;

        $this->nameGenerator        = new ExtendDbIdentifierNameGenerator();
    }

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $extendOptions = $schema->getExtendOptions();
            $toSchema      = clone $schema;

            foreach ($extendOptions as $className => $options) {
                /**
                 * should check for className existence because of "_rename_configs"
                 */
                if (!class_exists($className) || !isset($options['fields'])) {
                    continue;
                }

                $tableName = $this->entityMetadataHelper->getTableNameByEntityClass($className);
                $table     = $toSchema->getTable($tableName);
                $fields    = $options['fields'];

                foreach ($fields as $fieldName => $fieldConfig) {
                    $tableHasColumn = $table->hasColumn($fieldName);
                    if (! $tableHasColumn) {
                        throw new \RuntimeException(
                            sprintf('Cannot find column "%s" for "%s" table.', $fieldName, $tableName)
                        );
                    }

                    if (!isset($fieldConfig['type'])
                        || (
                            isset($fieldConfig['type'])
                            && !in_array($fieldConfig['type'], ['oneToMane', 'manyToMany', 'manyToOne', 'optionSet'])
                        )
                    ) {
                        $enabled = true;
                        if (
                            isset($fieldConfig['configs'])
                            && isset($fieldConfig['configs']['datagrid'])
                            && isset($fieldConfig['configs']['datagrid']['is_visible'])
                            && $fieldConfig['configs']['datagrid']['is_visible'] === false
                        ) {
                            $enabled = false;
                        }

                        $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                            $className,
                            $fieldName
                        );

                        $tableHasIndex = $table->hasIndex($indexName);
                        if ($enabled && !$tableHasIndex) {
                            $table->addIndex([$fieldName], $indexName);
                        }
                        if (!$enabled && $tableHasIndex) {
                            $table->dropIndex($indexName);
                        }
                    }
                }
            }

            /**
             * In case of renaming column name we should rename existing index
             * so we DROP existing one and create new with new name
             */
            $renames = isset($extendOptions['_rename_configs']) ? $extendOptions['_rename_configs'] : [];
            foreach ($renames as $className => $fields) {
                $tableName = $this->entityMetadataHelper->getTableNameByEntityClass($className);
                $table     = $toSchema->getTable($tableName);

                foreach ($fields as $oldFieldName => $newFieldName) {
                    $oldIndexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                        $className,
                        $oldFieldName
                    );

                    if ($table->hasIndex($oldIndexName)) {
                        $table->dropIndex($oldIndexName);
                        $this->renameExtension->addIndex(
                            $schema,
                            $queries,
                            $tableName,
                            [$newFieldName],
                            $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                                $className,
                                $newFieldName
                            )
                        );
                    }
                }
            }

            $sqlQueries = $toSchema->getMigrateFromSql($schema, $this->connection->getDatabasePlatform());
            $queries->addQuery(
                new UpdateExtendIndexMigrationQuery($sqlQueries)
            );
        };
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class UpdateExtendIndicesMigration implements
    Migration,
    DatabasePlatformAwareInterface,
    NameGeneratorAwareInterface,
    RenameExtensionAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     */
    public function __construct(EntityMetadataHelper $entityMetadataHelper)
    {
        $this->entityMetadataHelper = $entityMetadataHelper;
    }

    /**
     * @inheritdoc
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @inheritdoc
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
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

            foreach ($extendOptions as $key => $options) {
                $pair = explode('!', $key);
                if ($pair === 2) {
                    $tableName  = $pair[0];
                    $columnName = $pair[1];
                    $this->processColumn($toSchema, $queries, $tableName, $columnName, $options);
                }
            }

            $sqlQueries = $toSchema->getMigrateFromSql($schema, $this->platform);
            if (!empty($sqlQueries)) {
                $queries->addQuery(
                    new SqlMigrationQuery($sqlQueries)
                );
            }
        }
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $tableName
     * @param string   $columnName
     * @param array    $options
     */
    protected function processColumn(Schema $schema, QueryBag $queries, $tableName, $columnName, $options)
    {
        $className = $this->entityMetadataHelper->getEntityClassByTableName($tableName);
        $table     = $schema->getTable($tableName);

        if (!isset($options[ExtendOptionsManager::NEW_NAME_OPTION])) {
            if (isset($options[ExtendOptionsManager::TYPE_OPTION])) {
                $columnType = $options[ExtendOptionsManager::TYPE_OPTION];
                if (!in_array($columnType, ['oneToMane', 'manyToMany', 'manyToOne', 'optionSet'])) {
                    $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                        $className,
                        $columnName
                    );
                    $enabled   = !isset($options['datagrid']['is_visible']) || $options['datagrid']['is_visible'];
                    if ($enabled && !$table->hasIndex($indexName)) {
                        $table->addIndex([$columnName], $indexName);
                    } elseif (!$enabled && $table->hasIndex($indexName)) {
                        $table->dropIndex($indexName);
                    }
                }
            }
        } else {
            // in case of renaming column name we should rename existing index
            $newColumnName = $options[ExtendOptionsManager::NEW_NAME_OPTION];
            $indexName     = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                $className,
                $columnName
            );
            if ($table->hasIndex($indexName)) {
                $table->dropIndex($indexName);
                $newIndexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                    $className,
                    $newColumnName
                );
                $this->renameExtension->addIndex(
                    $schema,
                    $queries,
                    $tableName,
                    [$newColumnName],
                    $newIndexName
                );
            }
        }
    }
}

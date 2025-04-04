<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * Updates indexes for the extend entities.
 */
class UpdateExtendIndicesMigration implements
    Migration,
    DatabasePlatformAwareInterface,
    NameGeneratorAwareInterface,
    RenameExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use ExtendNameGeneratorAwareTrait;
    use RenameExtensionAwareTrait;

    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $extendOptions = $schema->getExtendOptions();
            $toSchema      = clone $schema;

            foreach ($extendOptions as $key => $options) {
                $pair = explode('!', $key);
                if (count($pair) === 2) {
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
        $classNames = $this->entityMetadataHelper->getEntityClassesByTableName($tableName);
        if (!$classNames) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn($columnName)) {
            return;
        }

        if (!isset($options[ExtendOptionsManager::NEW_NAME_OPTION])) {
            if (isset($options[ExtendOptionsManager::TYPE_OPTION])) {
                foreach ($classNames as $className) {
                    $this->buildIndex($columnName, $options, $className, $table);
                }
            }
        } else {
            // in case of renaming column name we should rename existing index
            foreach ($classNames as $className) {
                $this->renameIndex($schema, $queries, $tableName, $columnName, $options, $className, $table);
            }
        }
    }

    /**
     * @param string $columnName
     * @param array $options
     * @param string $className
     * @param Table $table
     */
    protected function buildIndex($columnName, $options, $className, $table)
    {
        $columnType = $this->fieldTypeHelper->getUnderlyingType($options[ExtendOptionsManager::TYPE_OPTION]);
        if ($this->fieldTypeHelper->isRelation($columnType)
            || \is_a(Type::getType($columnType), TextType::class, true)
            || \is_a(Type::getType($columnType), JsonType::class, true)
        ) {
            return;
        }

        $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
            $className,
            $columnName
        );
        if ($this->isEnabled($options)
            && $this->isExtended($options)
            && !$table->hasIndex($indexName)
        ) {
            $table->addIndex([$columnName], $indexName);
        } elseif (!$this->isEnabled($options) && $table->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string $columnName
     * @param array $options
     * @param string $className
     * @param Table $table
     */
    protected function renameIndex(
        Schema $schema,
        QueryBag $queries,
        $tableName,
        $columnName,
        $options,
        $className,
        $table
    ) {
        $newColumnName = $options[ExtendOptionsManager::NEW_NAME_OPTION];
        $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
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

    /**
     * @param array $options
     * @return bool
     */
    protected function isExtended($options)
    {
        return isset($options['extend']['is_extend']) && $options['extend']['is_extend'];
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function isEnabled($options)
    {
        if (!isset($options['datagrid']['is_visible']) || $options['datagrid']['is_visible']) {
            return true;
        }

        if (isset($options['extend']['unique']) && $options['extend']['unique']) {
            return true;
        }

        return false;
    }
}

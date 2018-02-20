<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
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
    /** @var AbstractPlatform */
    protected $platform;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var RenameExtension */
    protected $renameExtension;

    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param FieldTypeHelper      $fieldTypeHelper
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
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
            || in_array($columnType, [Type::TEXT])
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

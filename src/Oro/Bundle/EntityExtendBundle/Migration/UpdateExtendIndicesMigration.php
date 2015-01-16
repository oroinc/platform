<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;

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

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param FieldTypeHelper      $fieldTypeHelper
     * @param CommandExecutor      $commandExecutor
     * @param LoggerInterface      $logger
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
        $this->commandExecutor      = $commandExecutor;
        $this->logger               = $logger;
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
        xdebug_break();
        if ($schema instanceof ExtendSchema) {
//            $this->clearCache();
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
        $className = $this->entityMetadataHelper->getEntityClassByTableName($tableName);
        if (null === $className) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn($columnName)) {
            return;
        }

        if (!isset($options[ExtendOptionsManager::NEW_NAME_OPTION])) {
            if (isset($options[ExtendOptionsManager::TYPE_OPTION])) {
                $columnType = $this->fieldTypeHelper->getUnderlyingType($options[ExtendOptionsManager::TYPE_OPTION]);
                if ($this->fieldTypeHelper->isRelation($columnType)
                ) {
                    return;
                }
                $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                    $className,
                    $columnName
                );
                if (in_array($columnType, [Type::TEXT])) {
                    if ($table->hasIndex($indexName)) {
                        $table->dropIndex($indexName);
                    }
                    return;
                }

                $enabled   = !isset($options['datagrid']['is_visible']) || $options['datagrid']['is_visible'];
                if ($enabled && !$table->hasIndex($indexName)) {
                    $table->addIndex([$columnName], $indexName);
                } elseif (!$enabled && $table->hasIndex($indexName)) {
                    $table->dropIndex($indexName);
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
                $this->logger->notice(
                    'else - s:' . $schema . ' in:' . $newIndexName
                );
            }
        }
    }

    protected function clearCache()
    {
        set_time_limit(0);

        $exitCode = 0;
        $code = $this->commandExecutor->runCommand(
            'cache:clear',
            [''],
            $this->logger
        );

        if ($code !== 0) {
            $exitCode = $code;
        }

        $isSuccess = $exitCode === 0;

//        if ($isSuccess && $generateProxies) {
//            $this->generateProxies();
//        }

        return $isSuccess;
    }
}

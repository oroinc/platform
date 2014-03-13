<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class UpdateExtendIndexMigrationQuery implements MigrationQuery
{
    /** @var  EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var  ConfigProvider */
    protected $extendConfigProvider;

    /** @var  ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var Schema */
    protected $schema;

    /** @var QueryBag */
    protected $queries;

    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        ConfigProvider $extendConfigProvider,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        Schema $schema,
        QueryBag $queries
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->nameGenerator        = $nameGenerator;
        $this->schema               = $schema;
        $this->queries              = $queries;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Updates indexes for extend fields');

        $this->collectIndexQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * @inheritdoc
     */
    public function execute(Connection $connection, LoggerInterface $logger)
    {
        $logger->info('Update indexes for extend fields');

        $toSchema = $this->collectIndexQueries(new NullLogger());
        $queries  = $toSchema->getMigrateFromSql($this->schema, $connection->getDatabasePlatform());

        foreach ($queries as $query){
            $logger->notice($query);
            $connection->executeQuery($query);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @return Schema
     */
    protected function collectIndexQueries(LoggerInterface $logger, $dryRun = false)
    {
        $toSchema = clone $this->schema;

        /** @var ConfigInterface[] $entityConfigs */
        $entityConfigs = $this->extendConfigProvider->filter(
            function (ConfigInterface $config) {
                return $config->is('index');
            }
        );

        foreach ($entityConfigs as $entityConfig) {
            $entityIndexes = $entityConfig->get('index');
            $className     = $entityConfig->getId()->getClassName();
            $tableName     = $this->entityMetadataHelper->getTableNameByEntityClass($className);
            $table         = $toSchema->getTable($tableName);

            foreach ($entityIndexes as $fieldName => $enabled) {
                $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                    $className,
                    $fieldName
                );

                $tableHasIndex = $table->hasIndex($indexName);

                if ($enabled && $table->hasColumn($fieldName) && !$tableHasIndex) {
                    $logger->info('CREATE INDEX ' . $indexName . ' ON ' . $tableName . ' (' . $fieldName . ')');
                    $table->addIndex([$fieldName], $indexName);
                } elseif (!$enabled && $tableHasIndex) {
                    $logger->info('DROP INDEX ' . $indexName . ' ON ' . $tableName);
                    $table->dropIndex($indexName);
                }
            }
        }

        if (!$dryRun) {
            return $toSchema;
        }
    }
}

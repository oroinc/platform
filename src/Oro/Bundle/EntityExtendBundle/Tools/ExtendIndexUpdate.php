<?php
namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\DBAL\Schema\Schema;
use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ExtendIndexUpdate
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
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->nameGenerator        = $nameGenerator;

        $this->schema               = new Schema();
        $this->queries              = new QueryBag();
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @return Schema
     */
    public function collectIndexQueries(LoggerInterface $logger, $dryRun = false)
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

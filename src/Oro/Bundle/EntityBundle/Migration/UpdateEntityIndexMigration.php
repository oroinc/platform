<?php

namespace Oro\Bundle\EntityBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEntityIndexMigration implements Migration
{
    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param EntityClassResolver $entityClassResolver
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(EntityClassResolver $entityClassResolver, ConfigProvider $extendConfigProvider)
    {
        $this->entityClassResolver  = $entityClassResolver;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->nameGenerator        = new ExtendDbIdentifierNameGenerator();
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** @var ConfigInterface[] $entityConfigs */
        $entityConfigs = $this->extendConfigProvider->filter(
            function (ConfigInterface $config) {
                return $config->is('index');
            }
        );

        foreach ($entityConfigs as $entityConfig) {
            $indexes   = $entityConfig->get('index');
            $className = $entityConfig->getId()->getClassName();

            //$tableName = $this->entityClassResolver->getTableNameByEntityClass();
            $tableName = 'orocrm_account';
            $table     = $schema->getTable($tableName);

            foreach ($indexes as $fieldName => $enabled) {
                $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                    $className,
                    $fieldName
                );

                var_dump($indexName);

                $tableHasIndex = $table->hasIndex($indexName);

                if ($enabled && !$tableHasIndex) {
                    //$table->addIndex([$fieldName], $indexName);
                } elseif (!$enabled && $tableHasIndex) {
                    //$table->dropIndex($indexName);
                }
            }
        }
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Migration\CleanupBaseEnumClassesMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\MigrateEnumFieldConfigQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Update base enum related data with oro entity and field configuration.
 */
class UpdateBaseEnumRelatedDataMigration implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $entityConfigs = $this->connection->fetchAllAssociative(
            'SELECT id, class_name, data FROM oro_entity_config'
        );
        $enumClassNames = [];
        $metadataHelper = $this->getMetadataHelper();
        $nameGenerator = $this->getNameGenerator();
        foreach ($entityConfigs as $entityConfig) {
            $entityConfigData = $this->connection->convertToPHPValue($entityConfig['data'], 'array');
            if (!isset($entityConfigData['extend']['is_extend'])
                || !$entityConfigData['extend']['is_extend']
                || $entityConfig['class_name'] === EnumOption::class
                || str_starts_with($entityConfig['class_name'], ExtendHelper::ENTITY_NAMESPACE)) {
                continue;
            }
            $fieldConfigs = $this->connection->fetchAllAssociative(
                'SELECT * FROM oro_entity_config_field '
                . 'WHERE entity_id = :entity_id AND type IN (:enum_types)',
                ['entity_id' => $entityConfig['id'], 'enum_types' => ['enum', 'multiEnum']],
                ['entity_id' => Types::STRING, 'enum_types' => Connection::PARAM_STR_ARRAY]
            );
            $entityTableName = $metadataHelper->getTableNameByEntityClass($entityConfig['class_name']);
            foreach ($fieldConfigs as $fieldConfig) {
                $fieldConfigData = $this->connection->convertToPHPValue($fieldConfig['data'], Types::ARRAY);
                if (!isset($fieldConfigData['enum']['enum_code'])
                    || !isset($fieldConfigData['extend']['target_entity'])) {
                    continue;
                }
                // remove enum oro_entity_config field and move to serialized field config
                $queries->addQuery(
                    new MigrateEnumFieldConfigQuery(
                        $schema->getTable($entityTableName),
                        $entityConfig['class_name'],
                        $fieldConfig['field_name']
                    )
                );
                $enumClassNames[] = $fieldConfigData['extend']['target_entity'];
                $enumColumnName = UpdateExtendEntityEnumFieldsMigration::getBaseEnumColumnName(
                    $fieldConfig['type'],
                    $fieldConfig['field_name']
                );
                $enumTableName = $metadataHelper->getTableNameByEntityClass(
                    $fieldConfigData['extend']['target_entity']
                );
                // Check indexes which used enum column
                $entityMetadata = $this->getDoctrineHelper()->getEntityMetadata($entityConfig['class_name']);
                if (isset($entityMetadata->table['indexes'])) {
                    foreach ($entityMetadata->table['indexes'] as $indxName => $value) {
                        if (in_array($enumColumnName, $value['columns'])) {
                            $this->getLogger()->warning(sprintf(
                                'The index %s was removed because it used deleted enum column: %s',
                                $indxName,
                                $enumColumnName
                            ));
                            $schema->getTable($entityTableName)->dropIndex($indxName);
                        }
                    }
                }
                $queries->addPostQuery("ALTER TABLE $entityTableName DROP COLUMN IF EXISTS $enumColumnName");
                $queries->addPostQuery("DROP TABLE IF EXISTS $enumTableName CASCADE");
                if (ExtendHelper::isMultiEnumType($fieldConfig['type'])) {
                    $relationEnumTableName = $nameGenerator->generateManyToManyJoinTableName(
                        $entityConfig['class_name'],
                        $fieldConfig['field_name'],
                        $fieldConfigData['extend']['target_entity'],
                    );
                    $queries->addPostQuery("DROP TABLE IF EXISTS $relationEnumTableName");
                }
            }
        }
        if (!empty($enumClassNames)) {
            $queries->addPostQuery(new CleanupBaseEnumClassesMigrationQuery($enumClassNames));
        }
        // drop oro_enum_value_trans table
        if ($schema->hasTable('oro_enum_value_trans')) {
            $schema->dropTable('oro_enum_value_trans');
        }
    }

    private function getLogger(): LoggerInterface
    {
        return $this->container->get('logger');
    }

    private function getMetadataHelper(): EntityMetadataHelper
    {
        return $this->container->get('oro_entity_extend.migration.entity_metadata_helper');
    }

    private function getNameGenerator(): ExtendDbIdentifierNameGenerator
    {
        return $this->container->get('oro_entity_extend.db_id_name_generator');
    }

    private function getDoctrineHelper(): DoctrineHelper
    {
        return $this->container->get('oro_entity.doctrine_helper');
    }
}

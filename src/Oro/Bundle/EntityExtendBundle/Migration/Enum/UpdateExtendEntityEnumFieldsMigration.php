<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Container\ContainerInterface;

/**
 * Updates the data of the extended entity enumerable fields.
 */
class UpdateExtendEntityEnumFieldsMigration implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    protected const int BATCH_SIZE = 10000;

    private ?EnumFieldSerializedDataBatchUpdater $batchUpdater = null;

    public function __construct(protected ContainerInterface $container)
    {
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $entityConfigs = $this->connection->fetchAllAssociative(
            'SELECT id, class_name, data FROM oro_entity_config'
        );
        foreach ($entityConfigs as $entityConfig) {
            $entityConfig['data'] = $this->connection->convertToPHPValue(
                $entityConfig['data'],
                'array'
            );
            if ($this->isNotExtend($entityConfig)) {
                continue;
            }
            $queryString = 'SELECT field_name, type, data FROM oro_entity_config_field' .
                ' WHERE entity_id = :entity_id AND type IN (:enum_types)';
            $fieldConfigs = $this->connection->fetchAllAssociative(
                $queryString,
                ['entity_id' => $entityConfig['id'], 'enum_types' => ['enum', 'multiEnum']],
                ['entity_id' => Types::STRING, 'enum_types' => Connection::PARAM_STR_ARRAY]
            );
            foreach ($fieldConfigs as $fieldConfig) {
                $fieldConfigData = $fieldConfig['data'] = $this->connection->convertToPHPValue(
                    $fieldConfig['data'],
                    'array'
                );
                if (
                    !isset($fieldConfigData['enum']['enum_code'])
                    || !isset($fieldConfigData['extend']['target_entity'])
                ) {
                    continue;
                }
                $enumCode = $fieldConfigData['enum']['enum_code'];
                $enumOptions = $this->getEnumOptions($enumCode);
                if (empty($enumOptions)) {
                    continue;
                }
                $this->migrateEnumFieldOptions($schema, $entityConfig, $fieldConfig, $enumCode);
            }
        }
    }

    public static function getBaseEnumColumnName(string $type, string $fieldName): string
    {
        $relationPostfix = ExtendHelper::isMultiEnumType($type)
            ? ExtendDbIdentifierNameGenerator::SNAPSHOT_COLUMN_SUFFIX
            : ExtendDbIdentifierNameGenerator::RELATION_COLUMN_SUFFIX;

        return strtolower($fieldName . $relationPostfix);
    }

    private function isNotExtend(array $entityConfig): bool
    {
        return !isset($entityConfig['data']['extend']['is_extend'])
            || !$entityConfig['data']['extend']['is_extend']
            || $entityConfig['class_name'] === EnumOption::class
            || str_starts_with($entityConfig['class_name'], ExtendHelper::ENTITY_NAMESPACE);
    }

    private function migrateEnumFieldOptions(
        Schema $schema,
        array $entityConfig,
        array $fieldConfig,
        string $enumCode,
    ): void {
        $tableName = $entityConfig['data']['extend']['table'] ?? null;
        $entityClass = $entityConfig['class_name'];
        if (!$tableName) {
            $tableName = $entityConfig['data']['extend']['schema']['doctrine'][$entityClass]['table']
                ?? $this->getMetadataHelper()->getTableNameByEntityClass($entityClass)
                ?? null;
        }
        if (null === $tableName) {
            throw new \LogicException(sprintf('Undefined table name for entity: %s', $entityClass));
        }
        $idColumn = $this->getTableIdColumn($schema, $tableName);
        $idColumnName = $idColumn->getName();
        $enumColumnName = self::getBaseEnumColumnName($fieldConfig['type'], $fieldConfig['field_name']);
        $isMultiEnum = ExtendHelper::isMultiEnumType($fieldConfig['type']);

        if (!\in_array($idColumn->getType()->getName(), [Types::SMALLINT, Types::INTEGER, Types::BIGINT], true)) {
            $this->executeBatchUpdate(
                $tableName,
                $idColumnName,
                $enumColumnName,
                $fieldConfig['field_name'],
                $enumCode,
                $isMultiEnum,
            );

            return;
        }
        // Migrate table rows in parts
        $minId = $this->connection->executeQuery(
            sprintf('SELECT MIN(%s) FROM %s', $idColumnName, $tableName)
        )->fetchOne();
        if ($minId === null) {
            return;
        }
        $maxId = $this->connection->executeQuery(
            sprintf('SELECT MAX(%s) FROM %s', $idColumnName, $tableName)
        )->fetchOne();
        while ($minId <= $maxId) {
            $currentMax = $minId + self::BATCH_SIZE;
            if ($currentMax > $maxId) {
                $currentMax = $maxId;
            }
            $this->executeBatchUpdate(
                $tableName,
                $idColumnName,
                $enumColumnName,
                $fieldConfig['field_name'],
                $enumCode,
                $isMultiEnum,
                (int) $minId,
                (int) $currentMax,
            );
            $minId = $currentMax + 1;
        }
    }

    private function executeBatchUpdate(
        string $tableName,
        string $idColumnName,
        string $enumColumnName,
        string $fieldName,
        string $enumCode,
        bool $isMultiEnum,
        ?int $minId = null,
        ?int $maxId = null,
    ): void {
        if ($isMultiEnum) {
            $this->getBatchUpdater()->updateMultiEnum(
                $tableName,
                $idColumnName,
                $enumColumnName,
                $fieldName,
                $enumCode,
                $minId,
                $maxId,
            );

            return;
        }

        $this->getBatchUpdater()->updateEnum(
            $tableName,
            $idColumnName,
            $enumColumnName,
            $fieldName,
            $enumCode,
            $minId,
            $maxId,
        );
    }

    private function getBatchUpdater(): EnumFieldSerializedDataBatchUpdater
    {
        return $this->batchUpdater ??= new EnumFieldSerializedDataBatchUpdater($this->connection);
    }

    private function getTableIdColumn(Schema $schema, string $tableName): Column
    {
        $table = $schema->getTable($tableName);
        $primaryKeyColumns = $table->getPrimaryKey()->getColumns();
        $id = reset($primaryKeyColumns);

        return $table->getColumn($id);
    }

    private function getEnumOptions(string $enumCode): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT id FROM oro_enum_option WHERE enum_code = :enum_code',
            ['enum_code' => $enumCode]
        );
    }

    private function getMetadataHelper(): EntityMetadataHelper
    {
        return $this->container->get('oro_entity_extend.migration.entity_metadata_helper');
    }
}

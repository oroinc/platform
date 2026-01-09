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
                if (!isset($fieldConfigData['enum']['enum_code'])
                    || !isset($fieldConfigData['extend']['target_entity'])
                ) {
                    continue;
                }
                $enumCode = $fieldConfigData['enum']['enum_code'];
                $enumOptions = $this->getEnumOptions($enumCode);
                if (empty($enumOptions)) {
                    continue;
                }
                $this->migrateEnumFieldOptions($schema, $entityConfig, $fieldConfig, $enumOptions);
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
        array $serializedOptions
    ): void {
        $tableName = $entityConfig['data']['extend']['table'] ?? null;
        $entityClass = $entityConfig['class_name'];
        if (!$tableName) {
            $tableName = $entityData['data']['extend']['schema']['doctrine'][$entityClass]['table']
                ?? $this->getMetadataHelper()->getTableNameByEntityClass($entityClass)
                ?? null;
        }
        if (null === $tableName) {
            throw new \LogicException('Undefined table name: %s', $tableName);
        }
        $idColumn = $this->getTableIdColumn($schema, $tableName);
        $enumColumnName = self::getBaseEnumColumnName($fieldConfig['type'], $fieldConfig['field_name']);
        $query = "SELECT id, $enumColumnName, serialized_data FROM $tableName";
        // Migrate all table rows for non-numerical IDs
        if (!\in_array($idColumn->getType()->getName(), [Types::SMALLINT, Types::INTEGER, Types::BIGINT], true)) {
            $targetRows = $this->connection->fetchAllAssociative($query);
            ExtendHelper::isMultiEnumType($fieldConfig['type'])
                ? $this->migrateMultiEnum($enumColumnName, $tableName, $fieldConfig, $targetRows, $serializedOptions)
                : $this->migrateEnum($enumColumnName, $tableName, $fieldConfig, $targetRows, $serializedOptions);

            return;
        }
        // Migrate table rows in parts
        $minId = $this->connection->executeQuery("SELECT MIN(id) FROM $tableName")->fetchOne();
        // There are no records in this table
        if ($minId === null) {
            return;
        }
        $maxId = $this->connection->executeQuery("SELECT MAX(id) FROM $tableName")->fetchOne();
        while ($minId <= $maxId) {
            $currentMax = $minId + self::BATCH_SIZE;
            if ($currentMax > $maxId) {
                $currentMax = $maxId;
            }
            $targetRows = $this->connection->fetchAllAssociative(
                $query . ' WHERE id BETWEEN :minId AND :maxId',
                ['minId' => $minId, 'maxId' => $currentMax],
            );
            ExtendHelper::isMultiEnumType($fieldConfig['type'])
                ? $this->migrateMultiEnum($enumColumnName, $tableName, $fieldConfig, $targetRows, $serializedOptions)
                : $this->migrateEnum($enumColumnName, $tableName, $fieldConfig, $targetRows, $serializedOptions);
            $minId = $currentMax + 1;
        }
    }

    protected function migrateEnum(
        string $enumColumnName,
        string $tableName,
        array $fieldConfig,
        array $targetRows,
        array $serializedOptions,
    ): void {
        foreach ($targetRows as $targetRow) {
            $targetValue = $targetRow[$enumColumnName];
            if (null === $targetValue) {
                continue;
            }
            foreach ($serializedOptions as $serializedOption) {
                if (ExtendHelper::buildEnumOptionId($fieldConfig['data']['enum']['enum_code'], $targetValue)
                    !== $serializedOption['id']
                ) {
                    continue;
                }
                $previousSerializedData = null !== $targetRow['serialized_data']
                    ? \json_decode($targetRow['serialized_data'], true, 512, JSON_THROW_ON_ERROR)
                    : [];
                $targetRow['serialized_data'] = array_merge(
                    $previousSerializedData,
                    [$fieldConfig['field_name'] => $serializedOption['id']]
                );
                break;
            }
            $this->connection->executeQuery(
                "UPDATE $tableName SET serialized_data = :serialized_data WHERE  id = :id",
                ['serialized_data' => $targetRow['serialized_data'], 'id' => $targetRow['id']],
                ['serialized_data' => Types::JSON]
            );
        }
    }

    protected function migrateMultiEnum(
        string $enumColumnName,
        string $tableName,
        array $fieldConfig,
        array $targetRows,
        array $serializedOptions,
    ): void {
        foreach ($targetRows as $targetRow) {
            $targetValues = $targetRow[$enumColumnName];
            if (null === $targetValues) {
                continue;
            }
            $targetValueIds = array_map(
                function ($enumOptionId) use ($fieldConfig) {
                    return ExtendHelper::buildEnumOptionId(
                        $fieldConfig['data']['enum']['enum_code'],
                        $enumOptionId
                    );
                },
                explode(',', $targetValues)
            );
            $serializedOptionIds = [];
            foreach ($serializedOptions as $serializedOption) {
                if (!in_array($serializedOption['id'], $targetValueIds)) {
                    continue;
                }
                $serializedOptionIds[] = $serializedOption['id'];
            }
            if (empty($serializedOptionIds)) {
                continue;
            }
            $previousSerializedData = null !== $targetRow['serialized_data']
                ? \json_decode($targetRow['serialized_data'], true, 512, JSON_THROW_ON_ERROR)
                : [];
            $targetRow['serialized_data'] = array_merge(
                $previousSerializedData,
                [$fieldConfig['field_name'] => $serializedOptionIds],
            );
            $this->connection->executeQuery(
                "UPDATE $tableName SET serialized_data = :serialized_data WHERE  id = :id",
                ['serialized_data' => $targetRow['serialized_data'], 'id' => $targetRow['id']],
                ['serialized_data' => Types::JSON]
            );
        }
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

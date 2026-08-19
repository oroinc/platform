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

    /** @var EnumFieldSerializedDataBatchUpdater|null */
    private ?EnumFieldSerializedDataBatchUpdater $batchUpdater = null;

    /** @var string Primary key column of the table currently being migrated */
    private string $idColumnName = 'id';

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
                    || !isset($fieldConfigData['extend']['target_entity'])) {
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
        array $serializedOptions,
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
        $this->idColumnName = $idColumn->getName();
        $enumColumnName = self::getBaseEnumColumnName($fieldConfig['type'], $fieldConfig['field_name']);
        $isMultiEnum = ExtendHelper::isMultiEnumType($fieldConfig['type']);

        if (!\in_array($idColumn->getType()->getName(), [Types::SMALLINT, Types::INTEGER, Types::BIGINT], true)) {
            $isMultiEnum
                ? $this->migrateMultiEnum($enumColumnName, $tableName, $fieldConfig, [], $serializedOptions)
                : $this->migrateEnum($enumColumnName, $tableName, $fieldConfig, [], $serializedOptions);

            return;
        }
        // Migrate table rows in parts
        $minId = $this->connection->executeQuery(
            sprintf('SELECT MIN(%s) FROM %s', $this->idColumnName, $tableName)
        )->fetchOne();
        if ($minId === null) {
            return;
        }
        $maxId = $this->connection->executeQuery(
            sprintf('SELECT MAX(%s) FROM %s', $this->idColumnName, $tableName)
        )->fetchOne();
        while ($minId <= $maxId) {
            $currentMax = $minId + self::BATCH_SIZE;
            if ($currentMax > $maxId) {
                $currentMax = $maxId;
            }
            // Id bounds are passed via $targetRows to keep migrateEnum/migrateMultiEnum signatures for BC.
            $targetRows = [['id' => $minId], ['id' => $currentMax]];
            $isMultiEnum
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
        if (!$serializedOptions) {
            return;
        }

        [$minId, $maxId] = $this->resolveBatchIdRange($targetRows);

        $this->getBatchUpdater()->updateEnum(
            $tableName,
            $this->idColumnName,
            $enumColumnName,
            $fieldConfig['field_name'],
            $fieldConfig['data']['enum']['enum_code'],
            $minId,
            $maxId,
        );
    }

    protected function migrateMultiEnum(
        string $enumColumnName,
        string $tableName,
        array $fieldConfig,
        array $targetRows,
        array $serializedOptions,
    ): void {
        if (!$serializedOptions) {
            return;
        }

        [$minId, $maxId] = $this->resolveBatchIdRange($targetRows);

        $this->getBatchUpdater()->updateMultiEnum(
            $tableName,
            $this->idColumnName,
            $enumColumnName,
            $fieldConfig['field_name'],
            $fieldConfig['data']['enum']['enum_code'],
            $minId,
            $maxId,
        );
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveBatchIdRange(array $targetRows): array
    {
        if (!$targetRows) {
            return [null, null];
        }

        $ids = array_column($targetRows, 'id');
        if (!$ids) {
            return [null, null];
        }

        return [(int) min($ids), (int) max($ids)];
    }

    private function getBatchUpdater(): EnumFieldSerializedDataBatchUpdater
    {
        return $this->batchUpdater ??= new EnumFieldSerializedDataBatchUpdater($this->connection);
    }

    private function getTableIdColumn(Schema $schema, string $tableName): Column
    {
        $table = $schema->getTable($tableName);
        $primaryKeyColumns = $table->getPrimaryKeyColumns();
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

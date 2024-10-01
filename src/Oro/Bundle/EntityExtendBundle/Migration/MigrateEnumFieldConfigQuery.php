<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveOutdatedEnumFieldQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Log\LoggerInterface;

/**
 * Migrate enum field configuration to serialized.
 */
class MigrateEnumFieldConfigQuery extends RemoveOutdatedEnumFieldQuery
{
    public function __construct(private Table $entityTable, string $entityClass, string $enumField)
    {
        parent::__construct($entityClass, $enumField);
    }

    #[\Override]
    protected function deleteEnumData(LoggerInterface $logger, string $id, string $data): ?string
    {
        $enumClass = null;
        $isPublic = false;
        $immutable = false;
        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];
        // remove enum entity data
        if (!empty($data['extend']['target_entity'])) {
            $enumClass = $data['extend']['target_entity'];
            $enumRow = $this->connection->fetchAssociative(
                'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1',
                [$enumClass]
            );
            if (false !== $enumRow) {
                $enumEntityData = $this->connection->convertToPHPValue($enumRow['data'], Types::ARRAY);
                if (!empty($enumEntityData['enum']['immutable_codes'])) {
                    $this->moveImmutableCodes(
                        $data['enum']['enum_code'],
                        $enumEntityData['enum']['immutable_codes']
                    );
                }
                if (isset($enumEntityData['enum']['enum_public'])) {
                    $isPublic = $enumEntityData['enum']['enum_public'];
                }
                if (isset($enumEntityData['enum']['immutable'])) {
                    $immutable = $enumEntityData['enum']['immutable'];
                }
                $enumId = $enumRow['id'];
                // delete enum fields data
                $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE entity_id = ?', [$enumId]);
                // delete enum entity data
                $this->executeQuery($logger, 'DELETE FROM oro_entity_config WHERE class_name = ?', [$enumClass]);
            }
        }
        $data['extend']['is_serialized'] = true;
        // update config field data (removed relation options)
        unset($data['extend']['target_title']);
        unset($data['extend']['target_detailed']);
        unset($data['extend']['target_grid']);
        unset($data['extend']['target_entity']);
        unset($data['extend']['target_field']);

        if (isset($data['enum']['enum_options'])) {
            unset($data['enum']['enum_options']);
        }
        $data['enum']['enum_public'] = $isPublic;
        $data['enum']['immutable'] = $immutable;
        unset($data['extend']['relation_key']);
        $this->executeQuery(
            $logger,
            'UPDATE oro_entity_config_field SET data = :data WHERE id = :id',
            ['data' => $this->connection->convertToDatabaseValue($data, Types::ARRAY), 'id' => $id]
        );

        return $enumClass;
    }

    #[\Override]
    protected function updateEntityData(LoggerInterface $logger, string $enumClass, string $data): void
    {
        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];

        $extendKey = sprintf('manyToOne|%s|%s|%s', $this->entityClass, $enumClass, $this->enumField);
        if (isset($data['extend']['relation'][$extendKey])) {
            unset($data['extend']['relation'][$extendKey]);
        }
        // for Multi-Enum field type.
        $extendKey = sprintf('manyToMany|%s|%s|%s', $this->entityClass, $enumClass, $this->enumField);
        if (isset($data['extend']['relation'][$extendKey])) {
            unset($data['extend']['relation'][$extendKey]);
        }
        if (isset($data['extend']['schema']['relation'][$this->enumField])) {
            unset($data['extend']['schema']['relation'][$this->enumField]);
        }
        $enumSnapshotField = $this->enumField . ExtendHelper::ENUM_SNAPSHOT_SUFFIX;
        if (isset($data['extend']['schema']['property'][$enumSnapshotField])) {
            unset($data['extend']['schema']['property'][$enumSnapshotField]);
        }
        if (isset($data['extend']['schema']['doctrine'][$this->entityClass]['fields'][$enumSnapshotField])) {
            unset($data['extend']['schema']['doctrine'][$this->entityClass]['fields'][$enumSnapshotField]);
        }
        $data['extend']['schema']['serialized_property'][$this->enumField] = [];

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $this->executeQuery(
            $logger,
            'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
            [$data, $this->entityClass]
        );
    }

    private function moveImmutableCodes(string $enumCode, array $immutableCodes): void
    {
        $immutableEnumCodes = array_map(
            fn ($key) => ExtendHelper::buildEnumOptionId($enumCode, $key),
            $immutableCodes
        );
        $this->entityTable->addExtendColumnOption($this->enumField, 'enum', 'immutable_codes', $immutableEnumCodes);
    }
}

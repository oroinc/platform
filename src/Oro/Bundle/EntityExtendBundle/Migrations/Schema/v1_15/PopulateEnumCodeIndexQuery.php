<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Synchronizes the enum code index with stored enum field configuration values.
 */
class PopulateEnumCodeIndexQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
    public function getDescription(): string
    {
        return 'Populate indexed enum codes for existing enum field configurations.';
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $indexedValues = $this->getIndexedValues();
        $fieldConfigs = $this->connection->fetchAllAssociative(
            'SELECT id, data FROM oro_entity_config_field WHERE type IN (:types)',
            ['types' => ['enum', 'multiEnum']],
            ['types' => Connection::PARAM_STR_ARRAY]
        );

        foreach ($fieldConfigs as $fieldConfig) {
            $data = $this->connection->convertToPHPValue($fieldConfig['data'], Types::ARRAY);
            $enumCode = $data['enum']['enum_code'] ?? null;
            if (!\is_string($enumCode) || '' === $enumCode) {
                continue;
            }

            $fieldId = (int)$fieldConfig['id'];
            if (isset($indexedValues[$fieldId])) {
                if ($indexedValues[$fieldId]['value'] !== $enumCode) {
                    $this->connection->update(
                        'oro_entity_config_index_value',
                        ['value' => $enumCode],
                        ['id' => $indexedValues[$fieldId]['id']],
                        [Types::STRING],
                    );
                }
            } else {
                $this->connection->insert(
                    'oro_entity_config_index_value',
                    ['field_id' => $fieldId, 'scope' => 'enum', 'code' => 'enum_code', 'value' => $enumCode],
                    [Types::INTEGER, Types::STRING, Types::STRING, Types::STRING]
                );
            }
        }
    }

    private function getIndexedValues(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, field_id, value FROM oro_entity_config_index_value '
            . 'WHERE field_id IS NOT NULL AND scope = :scope AND code = :code',
            ['scope' => 'enum', 'code' => 'enum_code'],
            ['scope' => Types::STRING, 'code' => Types::STRING]
        );

        $indexedValues = [];
        foreach ($rows as $row) {
            $indexedValues[(int)$row['field_id']] = ['id' => (int)$row['id'], 'value' => $row['value']];
        }

        return $indexedValues;
    }
}

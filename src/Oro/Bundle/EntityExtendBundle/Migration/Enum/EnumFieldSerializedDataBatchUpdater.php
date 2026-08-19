<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Performs batch updates of serialized_data for enum and multiEnum extend fields.
 *
 * Replaces per-row PHP updates from {@see UpdateExtendEntityEnumFieldsMigration} with a single
 * SQL statement per batch. Batching follows
 * {@see \Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_2\ChangeSerializedDataFieldType::moveData()}.
 * Existing serialized_data keys are preserved via jsonb merge (||). Enum option ids are resolved
 * the same way as {@see ExtendHelper::buildEnumOptionId()}.
 */
class EnumFieldSerializedDataBatchUpdater
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function updateEnum(
        string $tableName,
        string $idColumnName,
        string $enumColumnName,
        string $fieldName,
        string $enumCode,
        ?int $minId = null,
        ?int $maxId = null,
    ): void {
        // Copies legacy scalar value (e.g. status_id) into serialized_data under $fieldName.
        $sql = sprintf(
            'UPDATE %s t
            SET serialized_data = COALESCE(t.serialized_data, \'{}\'::jsonb)
                || jsonb_build_object(\'%s\', o.id)
            FROM oro_enum_option o
            WHERE o.enum_code = :enumCode
                AND o.id = :enumPrefix || t.%s
                AND t.%s IS NOT NULL',
            $tableName,
            $this->escapeSqlLiteral($fieldName),
            $enumColumnName,
            $enumColumnName,
        );

        $this->execute($sql, $idColumnName, $enumCode, $minId, $maxId);
    }

    public function updateMultiEnum(
        string $tableName,
        string $idColumnName,
        string $enumColumnName,
        string $fieldName,
        string $enumCode,
        ?int $minId = null,
        ?int $maxId = null,
    ): void {
        $escapedFieldName = $this->escapeSqlLiteral($fieldName);
        // Splits comma-separated snapshot (e.g. tags_ss), maps each value to option id preserving order.
        $multiEnumValueSubQuery = sprintf(
            'SELECT COALESCE(jsonb_agg(o.id ORDER BY u.ord), \'[]\'::jsonb)
            FROM unnest(string_to_array(t.%s, \',\')) WITH ORDINALITY AS u(val, ord)
            INNER JOIN oro_enum_option o
                ON o.enum_code = :enumCode AND o.id = :enumPrefix || u.val
            WHERE u.val != \'\'',
            $enumColumnName,
        );
        // Skips rows where snapshot values cannot be mapped to any enum option.
        $existsSubQuery = sprintf(
            'SELECT 1
            FROM unnest(string_to_array(t.%s, \',\')) AS u(val)
            INNER JOIN oro_enum_option o
                ON o.enum_code = :enumCode AND o.id = :enumPrefix || u.val
            WHERE u.val != \'\'',
            $enumColumnName,
        );
        $sql = sprintf(
            'UPDATE %s t
            SET serialized_data = COALESCE(t.serialized_data, \'{}\'::jsonb)
                || jsonb_build_object(
                    \'%s\',
                    (%s)
                )
            WHERE t.%s IS NOT NULL
                AND EXISTS (%s)',
            $tableName,
            $escapedFieldName,
            $multiEnumValueSubQuery,
            $enumColumnName,
            $existsSubQuery,
        );

        $this->execute($sql, $idColumnName, $enumCode, $minId, $maxId);
    }

    private function execute(
        string $sql,
        string $idColumnName,
        string $enumCode,
        ?int $minId,
        ?int $maxId,
    ): void {
        $params = [
            'enumCode' => $enumCode,
            'enumPrefix' => $enumCode . ExtendHelper::ENUM_OPTION_SEPARATOR,
        ];

        // Optional id range limits the update to one batch.
        if ($minId !== null && $maxId !== null) {
            $sql .= sprintf(' AND t.%s BETWEEN :minId AND :maxId', $idColumnName);
            $params['minId'] = $minId;
            $params['maxId'] = $maxId;
        }

        $this->connection->executeQuery($sql, $params);
    }

    private function escapeSqlLiteral(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}

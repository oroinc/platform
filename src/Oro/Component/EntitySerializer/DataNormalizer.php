<?php

namespace Oro\Component\EntitySerializer;

/**
 * The result data normalizer.
 */
class DataNormalizer
{
    public function normalizeData(array $data, EntityConfig $config): array
    {
        $fields = $config->getFields();
        if (!empty($fields)) {
            $this->normalizeRows($data, $config);
        }

        return $data;
    }

    protected function normalizeRows(array &$rows, EntityConfig $config): void
    {
        foreach ($rows as $key => &$row) {
            if (ConfigUtil::INFO_RECORD_KEY !== $key && \is_array($row)) {
                $this->normalizeRow($row, $config);
            }
        }
    }

    protected function normalizeRow(array &$row, EntityConfig $config): void
    {
        $fields = $config->getFields();
        foreach ($fields as $field => $fieldConfig) {
            if ($fieldConfig->isExcluded()) {
                continue;
            }

            $targetConfig = $fieldConfig->getTargetEntity();
            if (null !== $targetConfig && !empty($row[$field]) && \is_array($row[$field])) {
                if ($fieldConfig->isCollapsed() && $targetConfig->get(ConfigUtil::COLLAPSE_FIELD)) {
                    $this->normalizeCollapsed($row, $field, $targetConfig->get(ConfigUtil::COLLAPSE_FIELD));
                } elseif (\array_key_exists(0, $row[$field])) {
                    $this->normalizeRows($row[$field], $targetConfig);
                } else {
                    $this->normalizeRow($row[$field], $targetConfig);
                }
            }
        }
        $this->removeExcludedFields($row, $config);
    }

    protected function normalizeCollapsed(array &$row, string $field, string $targetField): void
    {
        if (\array_key_exists(0, $row[$field])) {
            // to-many association
            $values = [];
            foreach ($row[$field] as $key => $value) {
                if (ConfigUtil::INFO_RECORD_KEY !== $key
                    && \is_array($value)
                    && \array_key_exists($targetField, $value)
                ) {
                    $value = $value[$targetField];
                }
                $values[$key] = $value;
            }
            $row[$field] = $values;
        } elseif (\array_key_exists($targetField, $row[$field])) {
            // to-one association
            $row[$field] = $row[$field][$targetField];
        }
    }

    protected function removeExcludedFields(array &$row, EntityConfig $config): void
    {
        $excludedFields = $config->get(ConfigUtil::EXCLUDED_FIELDS);
        if (!empty($excludedFields)) {
            foreach ($excludedFields as $field) {
                unset($row[$field]);
            }
        }
    }
}

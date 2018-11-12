<?php

namespace Oro\Component\EntitySerializer;

/**
 * Normalizes result data of the EntitySerializer.
 */
class DataNormalizer
{
    /**
     * Normalizes result data of the EntitySerializer
     *
     * @param array        $data
     * @param EntityConfig $config
     *
     * @return array
     */
    public function normalizeData(array $data, EntityConfig $config)
    {
        $fields = $config->getFields();
        if (!empty($fields)) {
            $this->normalizeRows($data, $config);
        }

        return $data;
    }

    /**
     * @param array        $rows
     * @param EntityConfig $config
     */
    protected function normalizeRows(array &$rows, EntityConfig $config)
    {
        foreach ($rows as $key => &$row) {
            if (ConfigUtil::INFO_RECORD_KEY !== $key && is_array($row)) {
                $this->normalizeRow($row, $config);
            }
        }
    }

    /**
     * @param array        $row
     * @param EntityConfig $config
     */
    protected function normalizeRow(array &$row, EntityConfig $config)
    {
        $fields = $config->getFields();
        foreach ($fields as $field => $fieldConfig) {
            if ($fieldConfig->isExcluded()) {
                continue;
            }

            $targetConfig = $fieldConfig->getTargetEntity();
            if (null !== $targetConfig && !empty($row[$field]) && is_array($row[$field])) {
                if ($fieldConfig->isCollapsed() && $targetConfig->get(ConfigUtil::COLLAPSE_FIELD)) {
                    $this->normalizeCollapsed($row, $field, $targetConfig->get(ConfigUtil::COLLAPSE_FIELD));
                } elseif (array_key_exists(0, $row[$field])) {
                    $this->normalizeRows($row[$field], $targetConfig);
                } else {
                    $this->normalizeRow($row[$field], $targetConfig);
                }
            }
        }
        $this->removeExcludedFields($row, $config);
    }

    /**
     * @param array  $row
     * @param string $field
     * @param string $targetField
     */
    protected function normalizeCollapsed(array &$row, $field, $targetField)
    {
        if (array_key_exists(0, $row[$field])) {
            // to-many association
            $values = [];
            foreach ($row[$field] as $key => $value) {
                if (ConfigUtil::INFO_RECORD_KEY !== $key
                    && is_array($value)
                    && array_key_exists($targetField, $value)
                ) {
                    $value = $value[$targetField];
                }
                $values[$key] = $value;
            }
            $row[$field] = $values;
        } else {
            // to-one association
            if (array_key_exists($targetField, $row[$field])) {
                $row[$field] = $row[$field][$targetField];
            }
        }
    }

    /**
     * @param array        $row
     * @param EntityConfig $config
     */
    protected function removeExcludedFields(array &$row, EntityConfig $config)
    {
        $excludedFields = $config->get(ConfigUtil::EXCLUDED_FIELDS);
        if (!empty($excludedFields)) {
            foreach ($excludedFields as $field) {
                unset($row[$field]);
            }
        }
    }
}

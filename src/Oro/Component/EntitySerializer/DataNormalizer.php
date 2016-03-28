<?php

namespace Oro\Component\EntitySerializer;

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
        foreach ($rows as &$row) {
            if (is_array($row)) {
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
            $targetConfig = $fieldConfig->getTargetEntity();
            if (!$fieldConfig->isExcluded()) {
                $propertyPath = $fieldConfig->getPropertyPath();
                if ($propertyPath
                    && (!array_key_exists($field, $row) || null !== $row[$field])
                ) {
                    $renaming     =
                        null !== $targetConfig
                        && !$fieldConfig->isCollapsed()
                        && $targetConfig->hasField($propertyPath);
                    $this->applyPropertyPath($row, $field, $propertyPath, $renaming);
                }
            }
            if (null !== $targetConfig && !empty($row[$field]) && is_array($row[$field])) {
                if (array_key_exists(0, $row[$field])) {
                    $this->normalizeRows($row[$field], $targetConfig);
                } else {
                    $this->normalizeRow($row[$field], $targetConfig);
                }
            }
        }
    }

    /**
     * @param array  $row
     * @param string $field
     * @param string $propertyPath
     * @param bool   $renaming
     */
    protected function applyPropertyPath(array &$row, $field, $propertyPath, $renaming)
    {
        if (!array_key_exists($field, $row)) {
            if (!$renaming) {
                $row[$field] = $this->extractValueByPropertyPath($row, $propertyPath);
            }
        } elseif (is_array($row[$field])) {
            if (array_key_exists(0, $row[$field])) {
                foreach ($row[$field] as &$subRow) {
                    $subRow = $this->extractValueByPropertyPath($subRow, $propertyPath);
                }
            } else {
                $row[$field] = $this->extractValueByPropertyPath($row[$field], $propertyPath);
            }
        } elseif (!$renaming && array_key_exists($propertyPath, $row) && $field !== $propertyPath) {
            $row[$field] = $row[$propertyPath];
            unset($row[$propertyPath]);
        }
    }

    /**
     * @param array  $row
     * @param string $propertyPath
     *
     * @return mixed
     */
    protected function extractValueByPropertyPath(array &$row, $propertyPath)
    {
        $result     = null;
        $properties = ConfigUtil::explodePropertyPath($propertyPath);
        $lastIndex  = count($properties) - 1;
        $i          = 0;
        $path       = [];
        $currentRow = &$row;
        while ($i <= $lastIndex) {
            $property = $properties[$i];
            if (null === $currentRow || !array_key_exists($property, $currentRow)) {
                break;
            }
            if ($i === $lastIndex) {
                // get property value
                $result = $currentRow[$property];
                // remove extracted property
                unset($currentRow[$property]);
                // remove empty containers
                $p = count($path) - 1;
                while ($p >= 0) {
                    $currentRow = &$path[$p][0];
                    if (!empty($currentRow[$path[$p][1]])) {
                        break;
                    }
                    unset($currentRow[$path[$p][1]]);
                    $p--;
                }
                break;
            }
            $path[]     = [&$currentRow, $property];
            $currentRow = &$currentRow[$property];
            $i++;
        }

        return $result;
    }
}

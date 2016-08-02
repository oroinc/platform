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
                if ($propertyPath) {
                    $path = ConfigUtil::explodePropertyPath($propertyPath);
                    if (!array_key_exists($path[0], $row) || null !== $row[$path[0]]) {
                        $this->applyPropertyPath($row, $field, $path);
                    } else {
                        $row[$field] = null;
                    }
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
     * @param array    $row
     * @param string   $field
     * @param string[] $propertyPath
     */
    protected function applyPropertyPath(array &$row, $field, array $propertyPath)
    {
        if (!array_key_exists($field, $row)) {
            $row[$field] = $this->extractValueByPropertyPath($row, $propertyPath);
        } elseif (is_array($row[$field])) {
            $childPropertyPath = array_slice($propertyPath, 1);
            if (array_key_exists(0, $row[$field])) {
                foreach ($row[$field] as &$subRow) {
                    $subRow = $this->extractValueByPropertyPath($subRow, $childPropertyPath);
                }
            } else {
                $row[$field] = $this->extractValueByPropertyPath($row[$field], $childPropertyPath);
            }
        } elseif (1 === count($propertyPath)) {
            $srcName = $propertyPath[0];
            if (array_key_exists($srcName, $row) && $field !== $srcName) {
                $row[$field] = $row[$srcName];
                unset($row[$srcName]);
            }
        }
    }

    /**
     * @param array    $row
     * @param string[] $propertyPath
     *
     * @return mixed
     */
    protected function extractValueByPropertyPath(array &$row, array $propertyPath)
    {
        $result     = null;
        $lastIndex  = count($propertyPath) - 1;
        $i          = 0;
        $path       = [];
        $currentRow = &$row;
        while ($i <= $lastIndex) {
            $property = $propertyPath[$i];
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

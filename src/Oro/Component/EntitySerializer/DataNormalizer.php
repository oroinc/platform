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
            if ($fieldConfig->isExcluded()) {
                unset($row[$field]);
            } else {
                $propertyPath = $this->getPropertyPath($field, $fieldConfig);
                if ($propertyPath) {
                    $path = ConfigUtil::explodePropertyPath($propertyPath);
                    if (!array_key_exists($path[0], $row) || null !== $row[$path[0]]) {
                        $this->applyPropertyPath($row, $field, $path);
                    } else {
                        $row[$field] = null;
                    }
                }
            }
            $targetConfig = $fieldConfig->getTargetEntity();
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
     * @param string      $field
     * @param FieldConfig $fieldConfig
     *
     * @return string|null
     */
    protected function getPropertyPath($field, FieldConfig $fieldConfig)
    {
        return $fieldConfig->getPropertyPath();
    }

    /**
     * @param array    $row
     * @param string   $field
     * @param string[] $propertyPath
     */
    protected function applyPropertyPath(array &$row, $field, array $propertyPath)
    {
        if (!array_key_exists($field, $row)) {
            if (array_key_exists($propertyPath[0], $row)
                && $propertyPath[0] !== $field
                && is_array($row[$propertyPath[0]])
                && (empty($row[$propertyPath[0]]) || array_key_exists(0, $row[$propertyPath[0]]))
            ) {
                $firstField = $propertyPath[0];
                $this->applyCollection($row, $firstField, array_slice($propertyPath, 1));
                $row[$field] = $row[$firstField];
                unset($row[$firstField]);
            } else {
                $row[$field] = $this->extractValueByPropertyPath($row, $propertyPath);
            }
        } elseif (is_array($row[$field])) {
            $childPropertyPath = array_slice($propertyPath, 1);
            if (empty($row[$field]) || array_key_exists(0, $row[$field])) {
                $this->applyCollection($row, $field, $childPropertyPath);
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
     * @param string   $field
     * @param string[] $propertyPath
     */
    protected function applyCollection(array &$row, $field, array $propertyPath)
    {
        $propertyPathLength = count($propertyPath);
        foreach ($row[$field] as &$subRow) {
            if ($propertyPathLength > 1 || (is_array($subRow) && array_key_exists($propertyPath[0], $subRow))) {
                $subRow = $this->extractValueByPropertyPath($subRow, $propertyPath);
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
            if (null === $currentRow) {
                $result = null;
                break;
            }
            if (!is_array($currentRow)) {
                throw new \RuntimeException(
                    sprintf(
                        'A value of "%s" field should be "null or array". Got: %s.',
                        implode('.', array_slice($propertyPath, 0, $i)),
                        is_object($currentRow) ? get_class($currentRow) : gettype($currentRow)
                    )
                );
            }
            if (!array_key_exists($property, $currentRow)) {
                $result = null;
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

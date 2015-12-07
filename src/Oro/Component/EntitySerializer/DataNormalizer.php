<?php

namespace Oro\Component\EntitySerializer;

class DataNormalizer
{
    /**
     * Normalizes result data of the EntitySerializer
     *
     * @param array $data
     * @param array $config
     *
     * @return array
     */
    public function normalizeData(array $data, array $config)
    {
        if (!empty($config[ConfigUtil::FIELDS])) {
            $this->normalizeRows($data, $config);
        }

        return $data;
    }

    /**
     * @param array $rows
     * @param array $config
     */
    protected function normalizeRows(array &$rows, array $config)
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $this->normalizeRow($row, $config);
            }
        }
    }

    /**
     * @param array $row
     * @param array $config
     */
    protected function normalizeRow(array &$row, array $config)
    {
        foreach ($config[ConfigUtil::FIELDS] as $field => $fieldConfig) {
            if (null !== $fieldConfig) {
                if (isset($fieldConfig[ConfigUtil::PROPERTY_PATH])
                    && !ConfigUtil::isExclude($fieldConfig)
                    && (!array_key_exists($field, $row) || null !== $row[$field])
                ) {
                    $this->applyPropertyPath($row, $field, $fieldConfig[ConfigUtil::PROPERTY_PATH]);
                }
                if (!empty($fieldConfig[ConfigUtil::FIELDS]) && !empty($row[$field]) && is_array($row[$field])) {
                    if (array_key_exists(0, $row[$field])) {
                        $this->normalizeRows($row[$field], $fieldConfig);
                    } else {
                        $this->normalizeRow($row[$field], $fieldConfig);
                    }
                }
            }
        }
    }

    /**
     * @param array  $row
     * @param string $field
     * @param string $propertyPath
     */
    protected function applyPropertyPath(array &$row, $field, $propertyPath)
    {
        if (!array_key_exists($field, $row)) {
            $row[$field] = $this->extractValueByPropertyPath($row, $propertyPath);
        } elseif (is_array($row[$field])) {
            if (array_key_exists(0, $row[$field])) {
                foreach ($row[$field] as &$subRow) {
                    $subRow = $this->extractValueByPropertyPath($subRow, $propertyPath);
                }
            } else {
                $row[$field] = $this->extractValueByPropertyPath($row[$field], $propertyPath);
            }
        } elseif (array_key_exists($propertyPath, $row)) {
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

<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

/**
 * @property array $items
 */
trait DataTypeTrait
{
    /**
     * Indicates whether the data type is set.
     *
     * @return bool
     */
    public function hasDataType()
    {
        return array_key_exists(EntityDefinitionFieldConfig::DATA_TYPE, $this->items);
    }

    /**
     * Gets expected data type of the filter value.
     *
     * @return string|null
     */
    public function getDataType()
    {
        return array_key_exists(EntityDefinitionFieldConfig::DATA_TYPE, $this->items)
            ? $this->items[EntityDefinitionFieldConfig::DATA_TYPE]
            : null;
    }

    /**
     * Sets expected data type of the filter value.
     *
     * @param string|null $dataType
     */
    public function setDataType($dataType)
    {
        if ($dataType) {
            $this->items[EntityDefinitionFieldConfig::DATA_TYPE] = $dataType;
        } else {
            unset($this->items[EntityDefinitionFieldConfig::DATA_TYPE]);
        }
    }
}

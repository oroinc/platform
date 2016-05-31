<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

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
        return array_key_exists(self::DATA_TYPE, $this->items);
    }

    /**
     * Gets expected data type of the filter value.
     *
     * @return string|null
     */
    public function getDataType()
    {
        return array_key_exists(self::DATA_TYPE, $this->items)
            ? $this->items[self::DATA_TYPE]
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
            $this->items[self::DATA_TYPE] = $dataType;
        } else {
            unset($this->items[self::DATA_TYPE]);
        }
    }
}

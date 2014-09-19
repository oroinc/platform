<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class MappedData implements DataInterface
{
    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var DataInterface
     */
    protected $sourceData;

    /**
     * @var PropertyAccessor
     */
    protected $accessor = null;

    /**
     * @param array         $mapping
     * @param DataInterface $sourceData
     */
    public function __construct(array $mapping, DataInterface $sourceData)
    {
        $this->mapping = $mapping;
        $this->sourceData = $sourceData;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this->sourceData->toArray() as $sourceKey => $sourceItem) {
            if ($this->hasNestedRecords($sourceItem)) {
                foreach ($sourceItem as $nestedItem) {
                    $record = array();
                    foreach ($this->mapping as $name => $fieldName) {
                        $record[$name] = $this->getValue($nestedItem, $fieldName);
                    }
                    $result[$sourceKey][] = $record;
                }
            } else {
                $record = array();
                foreach ($this->mapping as $name => $fieldName) {
                    $record[$name] = $this->getValue($sourceItem, $fieldName);
                }
                $result[] = $record;
            }
        }

        return $result;
    }

    /**
     * @return DataInterface
     */
    public function getSourceData()
    {
        return $this->sourceData;
    }

    /**
     * @param array|object $sourceItem
     * @param string $fieldName
     * @return mixed
     */
    protected function getValue($sourceItem, $fieldName)
    {
        if (is_array($sourceItem)) {
            $fieldName = "[$fieldName]";
        }

        return $this->getAccessor()->getValue($sourceItem, $fieldName);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getAccessor()
    {
        if ($this->accessor == null) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * @param $item
     * @return bool
     */
    protected function hasNestedRecords($item)
    {
        if (!is_array($item)) {
            return false;
        }

        $keys = array_keys($item);
        foreach ($keys as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }
}

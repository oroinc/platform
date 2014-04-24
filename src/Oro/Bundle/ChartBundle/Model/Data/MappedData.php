<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Symfony\Component\PropertyAccess\PropertyAccess;

class MappedData implements DataInterface
{
    protected $mapping;

    protected $sourceData;

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
        foreach ($this->sourceData->toArray() as $sourceItem) {
            $record = array();
            foreach ($this->mapping as $name => $fieldName) {
                $record[] = array($name => $this->getValue($sourceItem, $fieldName));
            }
            $result[] = $record;
        }

        return $result;
    }

    protected function getValue($sourceItem, $fieldName)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        return $accessor->getValue($sourceItem, $fieldName);
    }
}

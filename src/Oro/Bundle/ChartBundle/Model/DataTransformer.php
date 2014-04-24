<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class DataTransformer implements DataTransformerInterface
{
    /**
     * @param array                   $config
     * @param ResultRecordInterface[] $sourceData
     * @return array
     */
    public function transform(array $config, array $sourceData)
    {
        $result = array();
        foreach ($sourceData as $sourceRecord) {
            $record = array();
            foreach ($config as $name => $fieldName) {
                $record[] = array($name => $sourceRecord->getValue($fieldName));
            }
            $result[] = $record;
        }

        return $result;
    }
}

<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;

interface StrategyInterface
{
    /**
     * Merge field
     *
     * @param FieldData $fieldData
     */
    public function merge(FieldData $fieldData);

    /**
     * Checks if this class supports merging of passed field data
     *
     * @param FieldData $fieldData
     * @return bool
     */
    public function supports(FieldData $fieldData);
}

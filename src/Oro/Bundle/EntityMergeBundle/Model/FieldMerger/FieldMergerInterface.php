<?php

namespace Oro\Bundle\EntityMergeBundle\Model\FieldMerger;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;

interface FieldMergerInterface
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

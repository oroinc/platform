<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;

/**
 * Defines the contract for field merge strategies.
 *
 * Implementing classes provide different algorithms for merging field values from multiple
 * source entities into a master entity. Each strategy determines how to combine or select
 * values based on the merge mode and field characteristics. Strategies are responsible for
 * checking if they support a given field and executing the merge operation.
 */
interface StrategyInterface
{
    /**
     * Merge field
     */
    public function merge(FieldData $fieldData);

    /**
     * Checks if this class supports merging of passed field data
     *
     * @param FieldData $fieldData
     * @return bool
     */
    public function supports(FieldData $fieldData);

    /**
     * Get name of field merge strategy
     *
     * @return string
     */
    public function getName();
}

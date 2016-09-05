<?php

namespace Oro\Component\EntitySerializer\Filter;

/**
 * Filters fields from being showed/returned to the user
 */
interface EntityAwareFilterInterface
{
    const FILTER_ALL      = -1; // field should not be shown at all
    const FILTER_VALUE    = 0;  // field value should not be shown, return null against field's value
    const FILTER_NOTHING  = 1;  // field can be shown as is

    /**
     * @param object $entity      could be an object or array with entity data
     * @param string $entityClass in case when entity is array with data - this parameter is the source of truth
     * @param string $field
     *
     * @return int FILTER_ALL|FILTER_VALUE|FILTER_NOTHING
     */
    public function checkField($entity, $entityClass, $field);
}

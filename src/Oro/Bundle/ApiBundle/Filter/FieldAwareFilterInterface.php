<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface should be implemented by filters that applies to a field.
 */
interface FieldAwareFilterInterface
{
    /**
     * Sets a field by which the data is filtered.
     *
     * @param string $field The field name or property path
     */
    public function setField($field);
}

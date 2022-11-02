<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface should be implemented by filters that are applied to a field and need to know the field name.
 */
interface FieldAwareFilterInterface extends FieldFilterInterface
{
    /**
     * Sets a field by which the data is filtered.
     *
     * @param string $field The field name or property path
     */
    public function setField(string $field): void;

    /**
     * Get the field by which the data is filtered.
     */
    public function getField(): ?string;
}

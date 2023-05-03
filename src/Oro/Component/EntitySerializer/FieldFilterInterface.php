<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides an interface for classes that can make decision about entity fields availability.
 */
interface FieldFilterInterface
{
    public const FILTER_ALL     = -1; // field should not be shown at all
    public const FILTER_VALUE   = 0;  // field value should not be shown, return null against field's value
    public const FILTER_NOTHING = 1;  // field can be shown as is

    /**
     * Checks if it is allowed to return the given field or its value.
     *
     * @param object $entity      The entity object
     * @param string $entityClass The class name of the entity
     * @param string $field       The name of the entity field
     *
     * @return bool|null NULL if there is no any restrictions for the field and it can be shown as is
     *                   FALSE if the field value should not be shown and NULL should be returned instead of it
     *                   TRUE if the field should not be shown at all
     */
    public function checkField(object $entity, string $entityClass, string $field): ?bool;
}

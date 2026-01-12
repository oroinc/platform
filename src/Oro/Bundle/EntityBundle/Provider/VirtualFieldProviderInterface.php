<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Defines the contract for providing virtual entity fields.
 *
 * Implementations of this interface supply information about virtual fields
 * (computed or derived fields) that are not directly mapped in the database
 * but are available on entities.
 */
interface VirtualFieldProviderInterface
{
    /**
     * Indicates whether the given field is a virtual one or not
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function isVirtualField($className, $fieldName);

    /**
     * Gets a query associated with the given virtual field
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return array
     */
    public function getVirtualFieldQuery($className, $fieldName);

    /**
     * Gets virtual field names for given class
     *
     * @param string $className
     *
     * @return string[]
     */
    public function getVirtualFields($className);
}

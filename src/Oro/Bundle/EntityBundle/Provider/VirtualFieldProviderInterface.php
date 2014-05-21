<?php

namespace Oro\Bundle\EntityBundle\Provider;

interface VirtualFieldProviderInterface
{
    /**
     * Indicates whether the given field is a virtual one or not
     *
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function isVirtualField($className, $fieldName);

    /**
     * Gets a query associated with the given virtual field
     *
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    public function getVirtualFieldQuery($className, $fieldName);

    /**
     * Get virtual fields for given class
     *
     * @param string $className
     * @return array
     */
    public function getVirtualFields($className);
}

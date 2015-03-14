<?php

namespace Oro\Component\ConfigExpression\PropertyAccess;

interface PropertyPathInterface
{
    /**
     * Returns the string representation of the property path.
     *
     * @return string The path as string
     */
    public function __toString();

    /**
     * Returns the elements of the property path as array.
     *
     * @return array An array of property/index names
     */
    public function getElements();
}

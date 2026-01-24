<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

/**
 * Filters request parameters by converting them to boolean values.
 *
 * Uses PHP's {@see \filter_var function} with `FILTER_VALIDATE_BOOLEAN` to convert string
 * representations of boolean values to actual boolean types, returning null for invalid values.
 */
class BooleanParameterFilter implements ParameterFilterInterface
{
    #[\Override]
    public function filter($rawValue, $operator)
    {
        return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
    }
}

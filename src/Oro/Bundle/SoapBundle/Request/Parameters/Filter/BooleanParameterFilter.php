<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

class BooleanParameterFilter implements ParameterFilterInterface
{
    #[\Override]
    public function filter($rawValue, $operator)
    {
        return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
    }
}

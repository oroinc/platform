<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

class BooleanParameterFilter implements ParameterFilterInterface
{
    /**
     * {@inheritdoc}
     *
     * Returns TRUE for "1", 1, "true", true, "on", "yes".
     * Returns FALSE for "0", 0, "false", false, "off", "no", "" and null.
     * Returns NULL otherwise.
     */
    public function filter($rawValue, $operator)
    {
        return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
    }
}

<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

class CommaSeparatedParameterFilter implements ParameterFilterInterface
{
    const DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        return explode(self::DELIMITER, $rawValue);
    }
}

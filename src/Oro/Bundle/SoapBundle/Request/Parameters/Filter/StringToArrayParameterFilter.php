<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

/**
 * Filters request parameters by splitting strings into arrays.
 *
 * Converts string values into arrays by splitting on a configurable separator character,
 * allowing API clients to pass multiple values as a single delimited string parameter.
 */
class StringToArrayParameterFilter implements ParameterFilterInterface
{
    /** @var string */
    private $separator;

    /**
     * @param string $separator
     */
    public function __construct($separator = ',')
    {
        $this->separator = $separator;
    }

    #[\Override]
    public function filter($rawValue, $operator)
    {
        if ($rawValue) {
            return explode($this->separator, $rawValue);
        }

        return $rawValue;
    }
}

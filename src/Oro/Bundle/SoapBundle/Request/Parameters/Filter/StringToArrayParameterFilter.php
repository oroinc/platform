<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

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

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        if ($rawValue) {
            return explode($this->separator, $rawValue);
        }

        return $rawValue;
    }
}

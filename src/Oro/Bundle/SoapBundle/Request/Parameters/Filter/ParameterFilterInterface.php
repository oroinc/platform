<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

interface ParameterFilterInterface
{
    /**
     * Process filtering of request parameter
     *
     * @param mixed $rawValue
     * @param string $operator
     *
     * @return mixed
     */
    public function filter($rawValue, $operator);
}

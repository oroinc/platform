<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

/**
 * Defines the contract for filters that process SOAP API request parameters.
 *
 * Implementing filters transform or validate request parameter values, enabling
 * type conversion, normalization, and validation of incoming API request data.
 */
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

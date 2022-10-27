<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

/**
 * Applies filters chain to a parameter
 */
class ChainParameterFilter implements ParameterFilterInterface
{
    /** @var ParameterFilterInterface[] */
    protected $filters;

    /**
     * @param array|ParameterFilterInterface[] $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        foreach ($this->filters as $filter) {
            $rawValue = $filter->filter($rawValue, $operator);
        }

        return $rawValue;
    }
}

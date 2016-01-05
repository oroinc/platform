<?php

namespace Oro\Bundle\ApiBundle\Filter;

interface FilterFactoryInterface
{
    /**
     * Creates a filter for the given data-type.
     *
     * @param string $dataType
     *
     * @return FilterInterface|null
     */
    public function createFilter($dataType);
}

<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Creates a new instance of NestedAssociationFilter.
 */
class NestedAssociationFilterFactory
{
    public function __construct(
        private readonly ValueNormalizer $valueNormalizer
    ) {
    }

    public function createFilter(string $dataType): NestedAssociationFilter
    {
        $filter = new NestedAssociationFilter($dataType);
        $filter->setValueNormalizer($this->valueNormalizer);

        return $filter;
    }
}

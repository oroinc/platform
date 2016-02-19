<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

/**
 * Provides an interface for different kind of data filters.
 */
interface FilterInterface
{
    /**
     * Applies the filter to the Criteria object.
     *
     * @param Criteria         $criteria
     * @param FilterValue|null $value
     */
    public function apply(Criteria $criteria, FilterValue $value = null);

    /**
     * Creates an expression that can be used to in WHERE statement to filter data by this filter.
     *
     * @param FilterValue|null $value
     *
     * @return Expression|null
     */
    public function createExpression(FilterValue $value = null);
}

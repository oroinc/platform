<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * Provides an interface for different kind of data filters.
 */
interface FilterInterface
{
    /**
     * Applies the filter to the Criteria object.
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void;
}

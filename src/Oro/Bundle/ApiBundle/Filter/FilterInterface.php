<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * Provides an interface for different kind of filters of requested by API data.
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
}

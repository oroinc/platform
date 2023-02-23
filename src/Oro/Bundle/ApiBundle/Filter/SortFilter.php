<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * A filter that can be used to specify how a result collection should be sorted.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getSortFilterName
 */
class SortFilter extends StandaloneFilterWithDefaultValue
{
    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        $val = null !== $value
            ? $value->getValue()
            : $this->getDefaultValue();
        if (!empty($val)) {
            $criteria->orderBy($val);
        }
    }
}

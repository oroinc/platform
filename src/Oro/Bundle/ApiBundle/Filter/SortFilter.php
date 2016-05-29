<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * A filter that can be used to specify how a result collection should be sorted.
 */
class SortFilter extends StandaloneFilterWithDefaultValue
{
    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null)
    {
        $val = null !== $value
            ? $value->getValue()
            : $this->getDefaultValue();
        if (!empty($val)) {
            $criteria->orderBy($val);
        }
    }
}

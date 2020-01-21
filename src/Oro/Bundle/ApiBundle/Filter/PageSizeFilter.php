<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * A filter that can be used to specify the maximum number of records on one page.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getPageSizeFilterName
 */
class PageSizeFilter extends StandaloneFilterWithDefaultValue
{
    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        $val = null !== $value
            ? $value->getValue()
            : $this->getDefaultValue();
        if (null !== $val) {
            $criteria->setMaxResults($val);
        }
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;

/**
 * A filter that can be used to specify the maximum number of records on one page.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getPageSizeFilterName
 */
class PageSizeFilter extends StandaloneFilterWithDefaultValue
{
    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        $val = null !== $value
            ? $value->getValue()
            : $this->getDefaultValue();
        if (null !== $val) {
            if ($val < -1) {
                throw new InvalidFilterValueException('The value should be greater than or equals to -1.');
            }
            $criteria->setMaxResults($val);
        }
    }
}

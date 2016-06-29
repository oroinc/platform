<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

/**
 * A filter that can be used to specify the page number.
 */
class PageNumberFilter extends StandaloneFilterWithDefaultValue
{
    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null)
    {
        $val = null !== $value
            ? $value->getValue()
            : $this->getDefaultValue();
        if (null !== $val) {
            $pageSize = $criteria->getMaxResults();
            if (null !== $pageSize) {
                $criteria->setFirstResult(QueryUtils::getPageOffset($val, $pageSize));
            }
        }
    }
}

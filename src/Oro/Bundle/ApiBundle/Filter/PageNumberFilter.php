<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * A filter that can be used to specify the page number or the number of the first record in the response.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getPageNumberFilterName
 */
class PageNumberFilter extends StandaloneFilterWithDefaultValue
{
    private bool $isFirstRecordNumber = false;

    /**
     * Changes the behaviour of the filter to use the number of the first record
     * in the response instead of the page number.
     */
    public function useFirstRecordNumber(bool $isFirstRecordNumber = true): void
    {
        $this->isFirstRecordNumber = $isFirstRecordNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        $val = null !== $value
            ? $value->getValue()
            : $this->getDefaultValue();
        if (null !== $val) {
            if ($val < 1) {
                throw new InvalidFilterValueException('The value should be greater than or equals to 1.');
            }
            $pageSize = $criteria->getMaxResults();
            if (null !== $pageSize) {
                $criteria->setFirstResult(
                    $this->isFirstRecordNumber
                        ? $val - 1
                        : QueryBuilderUtil::getPageOffset($val, $pageSize)
                );
            }
        }
    }
}

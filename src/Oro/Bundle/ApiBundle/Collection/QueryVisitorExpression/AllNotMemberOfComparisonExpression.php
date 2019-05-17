<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

/**
 * Represents ALL NOT MEMBER OF comparison expression that checks
 * whether to-many association does not contain all of specific values.
 * This expression supports a scalar value and an array of scalar values.
 */
class AllNotMemberOfComparisonExpression extends AllMemberOfComparisonExpression
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedNumberOfRecords($value): int
    {
        return 0;
    }
}

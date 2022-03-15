<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Search;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Search\SearchAclHelperConditionInterface;

class TestSearchAclHelperCondition implements SearchAclHelperConditionInterface
{
    /** @var callable */
    private $isApplicableCallback;

    /** @var callable */
    private $addRestrictionCallback;

    public function __construct(callable $isApplicableCallback, callable $addRestrictionCallback)
    {
        $this->isApplicableCallback = $isApplicableCallback;
        $this->addRestrictionCallback = $addRestrictionCallback;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(string $className, string $permission): bool
    {
        return \call_user_func($this->isApplicableCallback, $className, $permission);
    }

    /**
     * {@inheritDoc}
     */
    public function addRestriction(Query $query, string $alias, ?Expression $orExpression): ?Expression
    {
        return \call_user_func($this->addRestrictionCallback, $query, $alias, $orExpression);
    }
}

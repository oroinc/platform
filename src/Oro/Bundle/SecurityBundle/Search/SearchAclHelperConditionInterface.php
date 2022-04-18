<?php

namespace Oro\Bundle\SecurityBundle\Search;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Search ACL helper condition builder.
 * This is temporary solution that will be replaced with
 * {@see \Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface} in one of the future LTS version.
 */
interface SearchAclHelperConditionInterface
{
    /**
     * Checks whether this condition can be applied to the given class and permission.
     */
    public function isApplicable(string $className, string $permission): bool;

    /**
     * Returns the search ACL expression with restrictions.
     */
    public function addRestriction(Query $query, string $alias, ?Expression $orExpression): ?Expression;
}

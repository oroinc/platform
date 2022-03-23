<?php

namespace Oro\Bundle\SecurityBundle\Search;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Returns the search ACL expression for the given class and permission by condition providers.
 * This helps to modify search ACL restrictions in case of custom logic.
 * This is temporary solution that will be replaced with
 * {@see \Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface} in one of the future LTS version.
 */
class SearchAclHelperConditionProvider
{
    /** @var iterable|SearchAclHelperConditionInterface[] */
    private iterable $aclHelperConditions;

    public function __construct(iterable $aclHelperConditions)
    {
        $this->aclHelperConditions = $aclHelperConditions;
    }

    public function isApplicable(string $className, string $permission): bool
    {
        foreach ($this->aclHelperConditions as $aclHelperCondition) {
            if ($aclHelperCondition->isApplicable($className, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the search ACL expressions for the given class and permission.
     */
    public function addRestriction(
        Query $query,
        string $className,
        string $permission,
        string $alias,
        ?Expression $orExpression = null
    ): ?Expression {
        foreach ($this->aclHelperConditions as $aclHelperCondition) {
            if ($aclHelperCondition->isApplicable($className, $permission)) {
                $orExpression = $aclHelperCondition->addRestriction($query, $alias, $orExpression);
            }
        }

        return $orExpression;
    }
}

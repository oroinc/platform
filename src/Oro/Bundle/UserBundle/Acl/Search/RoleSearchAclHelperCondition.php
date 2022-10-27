<?php

namespace Oro\Bundle\UserBundle\Acl\Search;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Search\SearchAclHelperConditionInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Adds additional restriction to search query to not return the anonymous role.
 */
class RoleSearchAclHelperCondition implements SearchAclHelperConditionInterface
{
    private AclConditionDataBuilderInterface $ownershipDataBuilder;

    public function __construct(AclConditionDataBuilderInterface $ownershipDataBuilder)
    {
        $this->ownershipDataBuilder = $ownershipDataBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(string $className, string $permission): bool
    {
        return is_a($className, Role::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function addRestriction(Query $query, string $alias, ?Expression $orExpression): ?Expression
    {
        $expressionBuilder = new ExpressionBuilder();
        $expression = $this->getExpressionByCondition(
            $this->ownershipDataBuilder->getAclConditionData(Role::class),
            $expressionBuilder
        );

        // in case if user have no access to roles
        if (null === $expression) {
            return null;
        }

        if (!\in_array($alias, $query->getFrom(), true)) {
            $query->from(array_merge($query->getFrom(), [$alias]));
        }
        if (null !== $orExpression) {
            $expression = $expressionBuilder->orX($orExpression, $expression);
        }

        return $expression;
    }

    private function getExpressionByCondition(
        array $condition,
        ExpressionBuilder $expressionBuilder
    ): ?Expression {
        if (count($condition) !== 0) {
            return null;
        }

        return $expressionBuilder->andX(
            $expressionBuilder->neq('role', User::ROLE_ANONYMOUS),
            $expressionBuilder->gte('integer.oro_access_role_', SearchListener::EMPTY_OWNER_ID)
        );
    }
}

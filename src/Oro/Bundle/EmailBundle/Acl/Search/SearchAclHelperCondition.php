<?php

namespace Oro\Bundle\EmailBundle\Acl\Search;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Search\SearchAclHelperConditionInterface;

/**
 * Applies restrictions for the public and private user emails.
 */
class SearchAclHelperCondition implements SearchAclHelperConditionInterface
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
        return 'VIEW' === $permission && is_a($className, EmailUser::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function addRestriction(Query $query, string $alias, ?Expression $orExpression): ?Expression
    {
        $expressionBuilder = new ExpressionBuilder();
        $publicExpression = $this->getExpressionByCondition(
            $this->ownershipDataBuilder->getAclConditionData(EmailUser::class),
            $expressionBuilder,
            0
        );
        $privateExpression = $this->getExpressionByCondition(
            $this->ownershipDataBuilder->getAclConditionData(EmailUser::class, 'VIEW_PRIVATE'),
            $expressionBuilder,
            1
        );

        // no access - do not modify the given expression and do not add alias to the list of available aliases
        if (null === $publicExpression && null === $privateExpression) {
            return $orExpression;
        }

        $query->from(array_merge($query->getFrom(), [$alias]));

        $expressions = [];
        if ($orExpression) {
            $expressions[] = $orExpression;
        }
        if (null !== $publicExpression) {
            $expressions[] = $publicExpression;
        }
        if (null !== $privateExpression) {
            $expressions[] = $privateExpression;
        }

        return $expressionBuilder->orX(...$expressions);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getExpressionByCondition(
        array $condition,
        ExpressionBuilder $expressionBuilder,
        int $privateValue
    ): ?Expression {
        if (count($condition) === 0) {
            return $expressionBuilder->andX(
                $expressionBuilder->eq('integer.email_user_private', $privateValue),
                $this->getNoLimitExpression($expressionBuilder)
            );
        }

        if (($condition[0] === null && $condition[2] === null)
            || ($condition[0] === null && $condition[1] === null && !$condition[4])
        ) {
            return null;
        }

        if ($condition[1] === null) {
            return $expressionBuilder->andX(
                $expressionBuilder->eq('integer.email_user_private', $privateValue),
                $this->getNoLimitExpression($expressionBuilder)
            );
        }

        $owners = !empty($condition[1])
            ? $condition[1]
            : SearchListener::EMPTY_OWNER_ID;

        if (\is_array($owners)) {
            return $expressionBuilder->andX(
                $expressionBuilder->eq('integer.email_user_private', $privateValue),
                count($owners) === 1
                    ? $expressionBuilder->eq('integer.oro_email_owner', reset($owners))
                    : $expressionBuilder->in('integer.oro_email_owner', $owners)
            );
        }

        return $expressionBuilder->andX(
            $expressionBuilder->eq('integer.email_user_private', $privateValue),
            $expressionBuilder->eq('integer.oro_email_owner', $owners)
        );
    }

    private function getNoLimitExpression(ExpressionBuilder $expressionBuilder): Expression
    {
        return $expressionBuilder->gte('integer.oro_email_owner', SearchListener::EMPTY_OWNER_ID);
    }
}

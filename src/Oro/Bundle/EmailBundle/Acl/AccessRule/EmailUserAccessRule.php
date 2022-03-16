<?php

namespace Oro\Bundle\EmailBundle\Acl\AccessRule;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\ExpressionInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;

/**
 * Access rule that limit data to the EmailUser entity according to the private/public view permissions.
 */
class EmailUserAccessRule implements AccessRuleInterface
{
    private AclConditionDataBuilderInterface $builder;

    public function __construct(AclConditionDataBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function process(Criteria $criteria): void
    {
        $alias = $criteria->getAlias();

        $viewPermissionExpression = $this->getExpressionWithEmailTypeRestriction(
            $alias,
            false,
            $criteria->getExpression()
        );
        $viewPrivatePermissionExpression = $this->getExpressionWithEmailTypeRestriction(
            $alias,
            true,
            $this->getPrivateAccessEmailsExpression($alias)
        );

        $ownerCriteria = null;
        if (null !== $viewPermissionExpression && null !== $viewPrivatePermissionExpression) {
            $ownerCriteria = new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [$viewPermissionExpression, $viewPrivatePermissionExpression]
            );
        } elseif (null !== $viewPermissionExpression) {
            $ownerCriteria = $viewPermissionExpression;
        } elseif (null !== $viewPrivatePermissionExpression) {
            $ownerCriteria = $viewPrivatePermissionExpression;
        }

        $criteria->setExpression($ownerCriteria);
    }

    private function getExpressionWithEmailTypeRestriction(
        string $alias,
        bool $privateValue,
        ?ExpressionInterface $expression
    ): ExpressionInterface {
        if ($privateValue) {
            $completeExpression = new Comparison(new Path('isEmailPrivate', $alias), Comparison::EQ, true);
        } else {
            $completeExpression = new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new Comparison(new Path('isEmailPrivate', $alias), Comparison::EQ, false),
                    new NullComparison(new Path('isEmailPrivate', $alias))
                ]
            );
        }

        if ($expression) {
            $completeExpression = new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [$expression, $completeExpression]
            );
        }

        return $completeExpression;
    }

    private function getPrivateAccessEmailsExpression(string $alias): ?ExpressionInterface
    {
        $conditionData = $this->builder->getAclConditionData(EmailUser::class, 'VIEW_PRIVATE');
        if (empty($conditionData)) {
            return null;
        }

        [$ownerField, $ownerValue, $organizationField, $organizationValue, $ignoreOwner] = $conditionData;

        if (!$ignoreOwner && empty($ownerValue)) {
            return new AccessDenied();
        }

        $expression = null;
        if (!$ignoreOwner) {
            $expression = new Comparison(
                new Path($ownerField, $alias),
                \is_array($ownerValue) ? Comparison::IN : Comparison::EQ,
                $ownerValue
            );
        }

        if (null !== $organizationField && null !== $organizationValue) {
            $orgExpression = new Comparison(new Path($organizationField, $alias), Comparison::EQ, $organizationValue);
            if ($expression) {
                $expression = new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [$expression, $orgExpression]
                );
            } else {
                $expression = $orgExpression;
            }
        }

        return $expression;
    }
}

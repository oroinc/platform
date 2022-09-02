<?php

namespace Oro\Bundle\UserBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Denies access to anonymous user role.
 */
class AnonymousRoleAccessRule implements AccessRuleInterface
{
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
        $criteria->andExpression(new Comparison(new Path('role'), Comparison::NEQ, User::ROLE_ANONYMOUS));
    }
}

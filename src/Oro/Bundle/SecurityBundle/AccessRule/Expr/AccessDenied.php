<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Access rule expression that deny access to an entity.
 */
class AccessDenied implements ExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function visit(Visitor $visitor)
    {
        return $visitor->walkAccessDenied($this);
    }
}

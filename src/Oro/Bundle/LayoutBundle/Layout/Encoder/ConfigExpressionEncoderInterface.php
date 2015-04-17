<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\Action;

interface ConfigExpressionEncoderInterface
{
    /**
     * Returns string representation of the given expression.
     *
     * @param ExpressionInterface $expr
     *
     * @return string
     */
    public function encodeExpr(ExpressionInterface $expr);

    /**
     * Returns string representation of the given action.
     *
     * @param Action[] $actions
     *
     * @return string
     */
    public function encodeActions($actions);
}

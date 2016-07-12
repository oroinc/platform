<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\Action;

interface ConfigExpressionEncoderInterface
{
    /**
     * Returns string representation of the given expression.
     *
     * @param ParsedExpression $expr
     *
     * @return string
     */
    public function encodeExpr(ParsedExpression $expr);

    /**
     * Returns string representation of the given action.
     *
     * @param Action[] $actions
     *
     * @return string
     */
    public function encodeActions($actions);
}

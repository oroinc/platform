<?php

namespace Oro\Component\Layout\ExpressionLanguage\Encoder;

use Oro\Component\Layout\Action;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * Defines the contract for encoding parsed expressions and actions into string representations.
 *
 * Implementations of this interface provide format-specific encoding of Symfony expression language
 * expressions and layout actions, allowing expressions to be serialized and transmitted in various formats.
 */
interface ExpressionEncoderInterface
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

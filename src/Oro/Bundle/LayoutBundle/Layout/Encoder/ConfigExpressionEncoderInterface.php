<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Oro\Component\ConfigExpression\ExpressionInterface;

interface ConfigExpressionEncoderInterface
{
    /**
     * Returns string representation of the given expression.
     *
     * @param ExpressionInterface $expr
     *
     * @return string
     */
    public function encode(ExpressionInterface $expr);
}

<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Oro\Component\ConfigExpression\ExpressionInterface;

class JsonConfigExpressionEncoder implements ConfigExpressionEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode(ExpressionInterface $expr)
    {
        return json_encode($expr->toArray());
    }
}

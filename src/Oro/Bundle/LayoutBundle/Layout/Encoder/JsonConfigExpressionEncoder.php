<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\Action;

class JsonConfigExpressionEncoder implements ConfigExpressionEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encodeExpr(ExpressionInterface $expr)
    {
        return json_encode($expr->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function encodeActions($actions)
    {
        return json_encode(
            [
                '@actions' => array_map(
                    function (Action $action) {
                        return [
                            'name' => $action->getName(),
                            'args' => $action->getArguments()
                        ];
                    },
                    $actions
                )
            ]
        );
    }
}

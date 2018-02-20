<?php

namespace Oro\Component\Layout\ExpressionLanguage\Encoder;

use Oro\Component\Layout\Action;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class JsonExpressionEncoder implements ExpressionEncoderInterface
{
    /**
     * @var ExpressionManipulator
     */
    protected $expressionManipulator;

    public function __construct(ExpressionManipulator $expressionManipulator)
    {
        $this->expressionManipulator = $expressionManipulator;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeExpr(ParsedExpression $expr)
    {
        return json_encode($this->expressionManipulator->toArray($expr));
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

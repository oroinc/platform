<?php

namespace Oro\Component\Layout\ExpressionLanguage\Encoder;

use Oro\Component\Layout\Action;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * Encodes parsed expressions and actions into JSON format.
 *
 * This encoder converts Symfony expression language expressions and layout actions into JSON strings,
 * using an expression manipulator to transform expressions into array representations suitable for JSON serialization.
 */
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

    #[\Override]
    public function encodeExpr(ParsedExpression $expr)
    {
        return json_encode($this->expressionManipulator->toArray($expr));
    }

    #[\Override]
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

<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Oro\Bundle\LayoutBundle\ExpressionLanguage\ExpressionManipulator;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\Action;

class JsonConfigExpressionEncoder implements ConfigExpressionEncoderInterface
{
    /** @var ExpressionManipulator  */
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

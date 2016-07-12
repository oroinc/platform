<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\Action;

class JsonConfigExpressionEncoder implements ConfigExpressionEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encodeExpr(ParsedExpression $expr)
    {
        return json_encode($this->serializeExpression($expr));
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

    /**
     * @param ParsedExpression $expr
     * @return array
     */
    protected function serializeExpression(ParsedExpression $expr)
    {
        return [
            'expression' => (string)$expr,
            'node' => $this->serializeExpressionNode($expr->getNodes())
        ];
    }

    /**
     * @param Node $node
     * @return array
     */
    protected function serializeExpressionNode(Node $node)
    {
        $result = [];
        $class = get_class($node);
        foreach ($node->attributes as $name => $value) {
            $result[$class]['attributes'][$name] = $value;
        }
        foreach ($node->nodes as $name => $node) {
            $result[$class]['nodes'][$name] = $this->serializeExpressionNode($node);
        }

        return $result;
    }
}

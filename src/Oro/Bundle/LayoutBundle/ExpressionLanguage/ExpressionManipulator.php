<?php

namespace Oro\Bundle\LayoutBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionManipulator
{
    /**
     * @param ParsedExpression $expr
     * @return array
     */
    public function toArray(ParsedExpression $expr)
    {
        return [
            'expression' => (string)$expr,
            'node' => $this->nodeToArray($expr->getNodes())
        ];
    }

    /**
     * @param Node $node
     * @return array
     */
    protected function nodeToArray(Node $node)
    {
        $result = [];
        $class = get_class($node);
        foreach ($node->attributes as $name => $value) {
            $result[$class]['attributes'][$name] = $value;
        }
        foreach ($node->nodes as $name => $node) {
            $result[$class]['nodes'][$name] = $this->nodeToArray($node);
        }

        return $result;
    }
}

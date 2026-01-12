<?php

namespace Oro\Component\Layout\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * Converts parsed expressions into array representations for serialization.
 *
 * This class recursively traverses the node tree of a parsed expression and converts it into an array
 * structure that captures both the expression string and the hierarchical node structure with their attributes.
 */
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

<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

/**
 * Represents "any" method call on a collection.
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 */
class CollectionMethodAnyNode extends AbstractCollectionMethodCallNode
{
    public static function getMethod(): string
    {
        return 'any';
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('call_user_func(function ($__variables) { ')
            ->raw('foreach ($__variables as $__name => $__value) ')
            ->raw('{ $$__name = $__value; } ')
            ->raw('$__result = false; foreach (')
            ->compile($this->nodes['node'])
            ->raw(' as $')
            ->raw(self::getSingularizedName($this->getNodeAttributeValue($this->nodes['node'])))
            ->raw(' ) { ')
            ->raw('$__evaluated_result = ')
            ->compile($this->nodes['arguments'])
            ->raw('; if ($__evaluated_result) { return true; } ')
            ->raw('$__result = $__result || $__evaluated_result; ')
            ->raw('} return $__result; ')
            ->raw('}, get_defined_vars())');
    }

    protected function doEvaluate(iterable $evaluatedNode, array $functions, array $values, string $itemName): bool
    {
        foreach ($evaluatedNode as $item) {
            if ($this->evaluateCollectionItem($functions, $values, $itemName, $item)) {
                return true;
            }
        }

        return false;
    }
}

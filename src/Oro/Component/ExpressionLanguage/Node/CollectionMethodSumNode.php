<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

/**
 * Represents "sum" method call on a collection.
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 */
class CollectionMethodSumNode extends AbstractCollectionMethodCallNode
{
    public static function getMethod(): string
    {
        return 'sum';
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
            ->raw('; $__result += $__evaluated_result; ')
            ->raw('} return $__result; ')
            ->raw('}, get_defined_vars())');
    }

    protected function doEvaluate(iterable $evaluatedNode, array $functions, array $values, string $itemName): mixed
    {
        $result = 0;
        foreach ($evaluatedNode as $item) {
            $evaluateItem = $this->evaluateCollectionItem($functions, $values, $itemName, $item);

            if (!is_numeric($evaluateItem)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to sum a non-numeric value %s in %s.',
                        var_export($evaluateItem, true),
                        $this->nodes['node']->dump()
                    )
                );
            }

            $result += $evaluateItem;
        }

        return $result;
    }
}

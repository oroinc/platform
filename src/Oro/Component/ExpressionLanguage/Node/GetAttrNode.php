<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Doctrine\Common\Inflector\Inflector;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Replacement of \Symfony\Component\ExpressionLanguage\Node\GetAttrNode only with "all", "any" and "sum" method calls
 */
class GetAttrNode extends Node
{
    /** @see getTypes method */
    const PROPERTY_CALL = 1;
    const ARRAY_CALL = 2;
    const ALL_CALL = 3;
    const ANY_CALL = 4;
    const SUM_CALL = 5;

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    /**
     * @param Node $node
     * @param Node $attribute
     * @param Node $arguments
     * @param int  $type
     */
    public function __construct(Node $node, Node $attribute, Node $arguments, $type)
    {
        parent::__construct(
            ['node' => $node, 'attribute' => $attribute, 'arguments' => $arguments],
            ['type' => $type]
        );
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler)
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('->')
                    ->raw($this->nodes['attribute']->attributes['value']);
                break;

            case self::ARRAY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('[')
                    ->compile($this->nodes['attribute'])->raw(']');
                break;

            case self::ALL_CALL:
                $compiler
                    ->raw('call_user_func(function ($__variables) { ')
                    ->raw('foreach ($__variables as $__name => $__value) ')
                    ->raw('{ $$__name = $__value; } ')
                    ->raw('$__result = true; foreach (')
                    ->compile($this->nodes['node'])
                    ->raw(' as $')
                    ->raw($this->getSingularizeName($this->getNodeAttributeValue($this->nodes['node'])))
                    ->raw(' ) { ')
                    ->raw('$__evaluated_result = ')
                    ->compile($this->nodes['arguments'])
                    ->raw('; if (!$__evaluated_result) { return false; } ')
                    ->raw('$__result = $__result && $__evaluated_result; ')
                    ->raw('} return $__result; ')
                    ->raw('}, get_defined_vars())');
                break;

            case self::ANY_CALL:
                $compiler
                    ->raw('call_user_func(function ($__variables) { ')
                    ->raw('foreach ($__variables as $__name => $__value) ')
                    ->raw('{ $$__name = $__value; } ')
                    ->raw('$__result = false; foreach (')
                    ->compile($this->nodes['node'])
                    ->raw(' as $')
                    ->raw($this->getSingularizeName($this->getNodeAttributeValue($this->nodes['node'])))
                    ->raw(' ) { ')
                    ->raw('$__evaluated_result = ')
                    ->compile($this->nodes['arguments'])
                    ->raw('; if ($__evaluated_result) { return true; } ')
                    ->raw('$__result = $__result || $__evaluated_result; ')
                    ->raw('} return $__result; ')
                    ->raw('}, get_defined_vars())');
                break;

            case self::SUM_CALL:
                $compiler
                    ->raw('call_user_func(function ($__variables) { ')
                    ->raw('foreach ($__variables as $__name => $__value) ')
                    ->raw('{ $$__name = $__value; } ')
                    ->raw('$__result = false; foreach (')
                    ->compile($this->nodes['node'])
                    ->raw(' as $')
                    ->raw($this->getSingularizeName($this->getNodeAttributeValue($this->nodes['node'])))
                    ->raw(' ) { ')
                    ->raw('$__evaluated_result = ')
                    ->compile($this->nodes['arguments'])
                    ->raw('; $__result += $__evaluated_result; ')
                    ->raw('} return $__result; ')
                    ->raw('}, get_defined_vars())');
                break;
        }
    }

    /**
     * @param array $functions
     * @param array $values
     *
     * @return bool|mixed
     */
    public function evaluate($functions, $values)
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                return $this->propertyEvaluate($functions, $values);

            case self::ARRAY_CALL:
                return $this->arrayEvaluate($functions, $values);

            case self::ALL_CALL:
                return $this->allEvaluate($functions, $values);

            case self::ANY_CALL:
                return $this->anyEvaluate($functions, $values);

            case self::SUM_CALL:
                return $this->sumEvaluate($functions, $values);
        }

        throw new \RuntimeException(
            sprintf(
                'Unable node type: %s. Available types are %s.',
                $this->attributes['type'],
                implode(', ', static::getTypes())
            )
        );
    }

    /**
     * @param array $functions
     * @param array $values
     *
     * @return bool
     */
    protected function propertyEvaluate($functions, $values)
    {
        $obj = $this->nodes['node']->evaluate($functions, $values);
        if (!is_object($obj) && !$obj instanceof \ArrayAccess && !is_array($obj)) {
            throw new \RuntimeException('Unable to get a property on a non-object.');
        }

        $property = $this->nodes['attribute']->attributes['value'];

        return $this->getPropertyAccessor()->getValue($obj, $property);
    }

    /**
     * @param array $functions
     * @param array $values
     *
     * @return bool
     */
    protected function arrayEvaluate($functions, $values)
    {
        $array = $this->nodes['node']->evaluate($functions, $values);
        if (!is_array($array) && !$array instanceof \ArrayAccess) {
            throw new \RuntimeException('Unable to get an item on a non-array.');
        }

        return $array[$this->nodes['attribute']->evaluate($functions, $values)];
    }

    /**
     * @param array $functions
     * @param array $values
     *
     * @return bool
     */
    protected function allEvaluate($functions, $values)
    {
        $obj = $this->nodes['node']->evaluate($functions, $values);

        $this->assertNodeIterable($obj);

        $result = true;
        foreach ($obj as $item) {
            $evaluateItem = $this->evaluateCollectionItem($functions, $values, $item);

            if (!$evaluateItem) {
                return false;
            }
            $result = $result && $evaluateItem;
        }

        return $result;
    }

    /**
     * @param array $functions
     * @param array $values
     *
     * @return bool
     */
    protected function anyEvaluate($functions, $values)
    {
        $obj = $this->nodes['node']->evaluate($functions, $values);

        $this->assertNodeIterable($obj);

        $result = false;
        foreach ($obj as $item) {
            $evaluateItem = $this->evaluateCollectionItem($functions, $values, $item);

            if ($evaluateItem) {
                return true;
            }
            $result = $result || $evaluateItem;
        }

        return $result;
    }

    /**
     * @param array $functions
     * @param array $values
     *
     * @return bool
     */
    protected function sumEvaluate($functions, $values)
    {
        $obj = $this->nodes['node']->evaluate($functions, $values);

        $this->assertNodeIterable($obj);

        $result = 0;
        foreach ($obj as $item) {
            $evaluateItem = $this->evaluateCollectionItem($functions, $values, $item);

            $this->assertNumeric($evaluateItem);

            $result += $evaluateItem;
        }

        return $result;
    }

    /**
     * @param array $functions
     * @param array $values
     * @param mixed $item
     *
     * @return mixed
     */
    protected function evaluateCollectionItem($functions, $values, $item)
    {
        $name = $this->getNodeAttributeValue($this->nodes['node']);
        $values[$this->getSingularizeName($name)] = $item;

        return $this->nodes['arguments']->evaluate($functions, $values);
    }

    /**
     * @param mixed $evaluatedNode
     */
    protected function assertNodeIterable($evaluatedNode)
    {
        if (!is_array($evaluatedNode) && $evaluatedNode instanceof \Traversable) {
            throw new \RuntimeException('Unable to iterate through a non-object.');
        }
    }

    /**
     * @param mixed $evaluateItem
     */
    protected function assertNumeric($evaluateItem)
    {
        if (!is_numeric($evaluateItem)) {
            throw new \RuntimeException(
                sprintf('Unable to sum a non-numeric value, value: %s.', var_export($evaluateItem, true))
            );
        }
    }

    /**
     * @return array
     */
    protected static function getTypes()
    {
        return [static::PROPERTY_CALL, static::ARRAY_CALL, static::ALL_CALL, static::ANY_CALL, static::SUM_CALL];
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (static::$propertyAccessor === null) {
            static::$propertyAccessor = new PropertyAccessor();
        }

        return static::$propertyAccessor;
    }

    /**
     * @param Node $node
     *
     * @return mixed
     */
    protected function getNodeAttributeValue(Node $node)
    {
        if ($node instanceof NameNode) {
            return $node->attributes['name'];
        } elseif ($node instanceof static) {
            return $node->nodes['attribute']->attributes['value'];
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getSingularizeName($name)
    {
        $singular = Inflector::singularize($name);
        if ($singular === $name) {
            return $name.'Item';
        }

        return $singular;
    }
}

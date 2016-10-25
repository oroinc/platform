<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Doctrine\Common\Inflector\Inflector;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Copy of \Symfony\Component\ExpressionLanguage\Node\GetAttrNode only with "all" and "any" method calls
 */
class GetAttrNode extends Node
{
    const PROPERTY_CALL = 1;
    const ARRAY_CALL = 2;
    const ALL_CALL = 3;
    const ANY_CALL = 4;

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    /**
     * @param Node $node
     * @param Node $attribute
     * @param Node $arguments
     * @param int $type
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

            case self::ARRAY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('[')
                    ->compile($this->nodes['attribute'])->raw(']');
                break;
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param array $functions
     * @param array $values
     * @return bool|mixed
     */
    public function evaluate($functions, $values)
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (!is_object($obj) && !$obj instanceof \ArrayAccess && !is_array($obj)) {
                    throw new \RuntimeException('Unable to get a property on a non-object.');
                }

                $property = $this->nodes['attribute']->attributes['value'];

                return $this->getPropertyAccessor()->getValue($obj, $property);

            case self::ARRAY_CALL:
                $array = $this->nodes['node']->evaluate($functions, $values);
                if (!is_array($array) && !$array instanceof \ArrayAccess) {
                    throw new \RuntimeException('Unable to get an item on a non-array.');
                }

                return $array[$this->nodes['attribute']->evaluate($functions, $values)];

            case self::ALL_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (!is_array($obj) && !$obj instanceof \Traversable) {
                    throw new \RuntimeException('Unable to iterate through a non-object.');
                }

                $name = $this->getNodeAttributeValue($this->nodes['node']);
                $result = true;
                foreach ($obj as $item) {
                    $evaluateResult = $this->nodes['arguments']
                        ->evaluate($functions, array_merge($values, [
                            $this->getSingularizeName($name) => $item
                        ]));
                    if (!$evaluateResult) {
                        return false;
                    }
                    $result = $result && $evaluateResult;
                }

                return $result;

            case self::ANY_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (!is_array($obj) && !$obj instanceof \Traversable) {
                    throw new \RuntimeException('Unable to iterate through a non-object.');
                }

                $name = $this->getNodeAttributeValue($this->nodes['node']);
                $result = false;
                foreach ($obj as $item) {
                    $evaluateResult = $this->nodes['arguments']
                        ->evaluate($functions, array_merge($values, [
                            $this->getSingularizeName($name) => $item
                        ]));
                    if ($evaluateResult) {
                        return true;
                    }
                    $result = $result || $evaluateResult;
                }

                return $result;
        }
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

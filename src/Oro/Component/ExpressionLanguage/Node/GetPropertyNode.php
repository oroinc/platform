<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Represents property a call.
 * Differs from {@see \Symfony\Component\ExpressionLanguage\Node\GetAttrNode}
 * by using {@see PropertyAccessorWithDotArraySyntax}
 * that gives the ability to access both array elements and objects properties.
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 * Originally extracted from {@see https://github.com/symfony/expression-language/blob/v5.3.7/Node/GetAttrNode.php}
 */
class GetPropertyNode extends Node
{
    private static ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(Node $node, Node $attribute, ArrayNode $arguments)
    {
        parent::__construct(['node' => $node, 'attribute' => $attribute, 'arguments' => $arguments]);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->nodes['node'])
            ->raw('->')
            ->raw($this->nodes['attribute']->attributes['value']);
    }

    public function evaluate(array $functions, array $values)
    {
        $evaluatedNode = $this->nodes['node']->evaluate($functions, $values);
        if (!\is_object($evaluatedNode) && !$evaluatedNode instanceof \ArrayAccess && !is_array($evaluatedNode)) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to get property "%s" of non-object "%s".',
                    $this->nodes['attribute']->dump(),
                    $this->nodes['node']->dump()
                )
            );
        }

        $property = $this->nodes['attribute']->attributes['value'];

        return self::getPropertyAccessor()->getValue($evaluatedNode, $property);
    }

    private static function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (self::$propertyAccessor === null) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax();
        }

        return self::$propertyAccessor;
    }

    public function toArray(): array
    {
        return [$this->nodes['node'], '.', $this->nodes['attribute']];
    }
}

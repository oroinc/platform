<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Doctrine\Inflector\Inflector;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode as SymfonyGetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Abstract node implementation representing a method call on a collection.
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 */
abstract class AbstractCollectionMethodCallNode extends Node
{
    protected static ?Inflector $inflector = null;

    public function __construct(Node $node, Node $attribute, ArrayNode $arguments)
    {
        if (count($arguments->nodes) !== 2) {
            throw new \RuntimeException(
                sprintf('Method %s() should have exactly one argument', static::getMethod())
            );
        }

        parent::__construct(['node' => $node, 'attribute' => $attribute, 'arguments' => $arguments]);
    }

    abstract public static function getMethod(): string;

    public function evaluate(array $functions, array $values)
    {
        $evaluatedNode = $this->nodes['node']->evaluate($functions, $values);

        if (!is_array($evaluatedNode) && !$evaluatedNode instanceof \Traversable) {
            throw new \RuntimeException(sprintf('Unable to iterate on "%s".', $this->nodes['node']->dump()));
        }

        $itemName = self::getSingularizedName($this->getNodeAttributeValue($this->nodes['node']));

        return $this->doEvaluate($evaluatedNode, $functions, $values, $itemName);
    }

    abstract protected function doEvaluate(
        iterable $evaluatedNode,
        array $functions,
        array $values,
        string $itemName
    ): mixed;

    protected function evaluateCollectionItem(array $functions, array $values, string $itemName, mixed $item): mixed
    {
        $values[$itemName] = $item;

        return current($this->nodes['arguments']->evaluate($functions, $values));
    }

    protected function getNodeAttributeValue(Node $node): string
    {
        if ($node instanceof NameNode) {
            return $node->attributes['name'];
        }

        if ($node instanceof GetPropertyNode || $node instanceof SymfonyGetAttrNode) {
            return $node->nodes['attribute']->attributes['value'];
        }

        throw new \RuntimeException(sprintf('Unable to get name of iterable "%s".', $this->nodes['node']->dump()));
    }

    protected static function getSingularizedName(string $name): string
    {
        $singular = self::getInflector()->singularize($name);
        if ($singular === $name) {
            return $name . 'Item';
        }

        return $singular;
    }

    protected static function getInflector(): Inflector
    {
        if (self::$inflector === null) {
            self::$inflector = InflectorFactory::create();
        }

        return self::$inflector;
    }

    public function toArray(): array
    {
        return [$this->nodes['node'], '.', $this->nodes['attribute'], '(', $this->nodes['arguments'], ')'];
    }
}

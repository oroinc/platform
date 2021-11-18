<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode as SymfonyGetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Depending on the attribute type creates a node object representing either:
 * - property call
 * - array call
 * - method call
 *
 * Throws an exception if method name is not one of [all, any, sum].
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 */
class GetAttrNodeFactory
{
    public const PROPERTY_CALL = 1;
    public const METHOD_CALL = 2;
    public const ARRAY_CALL = 3;

    public static function createNode(Node $node, Node $attribute, ArrayNode $arguments, int $type): Node
    {
        switch ($type) {
            case self::ARRAY_CALL:
                return new SymfonyGetAttrNode($node, $attribute, $arguments, $type);

            case self::PROPERTY_CALL:
                return new GetPropertyNode($node, $attribute, $arguments);

            case self::METHOD_CALL:
                $methodName = strtolower($attribute->attributes['value'] ?? '');
                return match ($methodName) {
                    CollectionMethodAllNode::getMethod() => new CollectionMethodAllNode($node, $attribute, $arguments),
                    CollectionMethodAnyNode::getMethod() => new CollectionMethodAnyNode($node, $attribute, $arguments),
                    CollectionMethodSumNode::getMethod() => new CollectionMethodSumNode($node, $attribute, $arguments),
                    default => throw new \RuntimeException(
                        sprintf(
                            'Unsupported method: %s(), supported methods are %s()',
                            $methodName,
                            implode(
                                '(), ',
                                [
                                    CollectionMethodAllNode::getMethod(),
                                    CollectionMethodAnyNode::getMethod(),
                                    CollectionMethodSumNode::getMethod(),
                                ]
                            )
                        )
                    )
                };
        }

        throw new \LogicException(sprintf('Undefined attribute type %s', $type));
    }
}

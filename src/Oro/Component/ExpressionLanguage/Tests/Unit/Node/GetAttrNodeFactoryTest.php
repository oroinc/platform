<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Oro\Component\ExpressionLanguage\Node\CollectionMethodAllNode;
use Oro\Component\ExpressionLanguage\Node\CollectionMethodAnyNode;
use Oro\Component\ExpressionLanguage\Node\CollectionMethodSumNode;
use Oro\Component\ExpressionLanguage\Node\GetAttrNodeFactory;
use Oro\Component\ExpressionLanguage\Node\GetPropertyNode;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode as SymfonyGetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

class GetAttrNodeFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateNodeReturnsSymfonyGetAttrNodeWhenTypeArrayCall(): void
    {
        $node = new Node();
        $attribute = new Node();
        $arguments = new ArrayNode();
        $type = GetAttrNodeFactory::ARRAY_CALL;

        self::assertEquals(
            new SymfonyGetAttrNode($node, $attribute, $arguments, $type),
            GetAttrNodeFactory::createNode($node, $attribute, $arguments, $type)
        );
    }

    public function testCreateNodeReturnsGetPropertyNodeWhenTypePropertyCall(): void
    {
        $node = new Node();
        $attribute = new Node();
        $arguments = new ArrayNode();
        $type = GetAttrNodeFactory::PROPERTY_CALL;

        self::assertEquals(
            new GetPropertyNode($node, $attribute, $arguments),
            GetAttrNodeFactory::createNode($node, $attribute, $arguments, $type)
        );
    }

    /**
     * @dataProvider collectionMethodCallNodeDataProvider
     *
     * @param string $methodName
     * @param string $expected
     */
    public function testCreateNodeReturnsCollectionMethodCallNodeWhenTypeMethodCall(
        string $methodName,
        string $expected
    ): void {
        $node = new Node();
        $attribute = new Node([], ['value' => $methodName]);
        $arguments = new ArrayNode();
        $arguments->addElement(new ArrayNode());
        $type = GetAttrNodeFactory::METHOD_CALL;

        self::assertEquals(
            new $expected($node, $attribute, $arguments),
            GetAttrNodeFactory::createNode($node, $attribute, $arguments, $type)
        );
    }

    /**
     * @dataProvider collectionMethodCallNodeDataProvider
     *
     * @param string $methodName
     * @param string $expected
     */
    public function testCreateNodeReturnsCollectionMethodCallNodeWhenTypeMethodCallUppercase(
        string $methodName,
        string $expected
    ): void {
        $node = new Node();
        $attribute = new Node([], ['value' => strtoupper($methodName)]);
        $arguments = new ArrayNode();
        $arguments->addElement(new ArrayNode());
        $type = GetAttrNodeFactory::METHOD_CALL;

        self::assertEquals(
            new $expected($node, $attribute, $arguments),
            GetAttrNodeFactory::createNode($node, $attribute, $arguments, $type)
        );
    }

    public function collectionMethodCallNodeDataProvider(): array
    {
        return [
            [
                'methodName' => CollectionMethodAllNode::getMethod(),
                'expected' => CollectionMethodAllNode::class,
            ],
            [
                'methodName' => CollectionMethodAnyNode::getMethod(),
                'expected' => CollectionMethodAnyNode::class,
            ],
            [
                'methodName' => CollectionMethodSumNode::getMethod(),
                'expected' => CollectionMethodSumNode::class,
            ],
        ];
    }

    public function testCreateNodeThrowsExceptionWhenMethodIsNotSupported(): void
    {
        $node = new Node();
        $attribute = new Node([], ['value' => 'unsupported']);
        $arguments = new ArrayNode();
        $arguments->addElement(new ArrayNode());
        $type = GetAttrNodeFactory::METHOD_CALL;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unsupported method: unsupported(), supported methods are all(), any(), sum()'
        );

        GetAttrNodeFactory::createNode($node, $attribute, $arguments, $type);
    }

    public function testCreateNodeThrowsExceptionWhenUndefinedType(): void
    {
        $node = new Node();
        $attribute = new Node();
        $arguments = new ArrayNode();
        $type = 42;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Undefined attribute type 42');

        GetAttrNodeFactory::createNode($node, $attribute, $arguments, $type);
    }
}

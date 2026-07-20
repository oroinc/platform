<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\NodeVisitor;

use Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode;
use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeGetAttrNodeVisitor;
use Oro\Bundle\EntityExtendBundle\Twig\Node\GetAttrNode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Template;

class SafeGetAttrNodeVisitorTest extends TestCase
{
    private SafeGetAttrNodeVisitor $visitor;
    private Environment&MockObject $env;
    private GetAttrNode $getAttrNode;
    private GetAttrNode $safeGetAttrNode;

    #[\Override]
    protected function setUp(): void
    {
        $this->visitor = new SafeGetAttrNodeVisitor();
        $this->env = $this->createMock(Environment::class);

        $this->getAttrNode = new GetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            ['type' => Template::ANY_CALL, 'ignore_strict_check' => false, 'optimizable' => false],
            1
        );

        $this->safeGetAttrNode = new SafeGetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            ['type' => Template::ANY_CALL, 'ignore_strict_check' => false, 'optimizable' => false],
            1
        );
    }

    public function testPriorityIsOne(): void
    {
        self::assertSame(1, $this->visitor->getPriority());
    }

    public function testLeaveNodeReturnsNodeUnchanged(): void
    {
        $node = new Node();

        self::assertSame($node, $this->visitor->leaveNode($node, $this->env));
    }

    public function testEnterNodeDoesNotReplaceArbitraryNode(): void
    {
        $node = new Node();

        self::assertSame($node, $this->visitor->enterNode($node, $this->env));
    }

    /**
     * SafeGetAttrNode is itself a subclass of GetAttrNode and must not be replaced again
     * to avoid infinite loops and double-wrapping.
     */
    public function testEnterNodeDoesNotReplaceSubclassOfGetAttrNode(): void
    {
        self::assertSame($this->safeGetAttrNode, $this->visitor->enterNode($this->safeGetAttrNode, $this->env));
    }

    public function testEnterNodeReplacesExactGetAttrNodeWithSafeGetAttrNode(): void
    {
        $result = $this->visitor->enterNode($this->getAttrNode, $this->env);

        self::assertInstanceOf(SafeGetAttrNode::class, $result);
        self::assertSame(get_class($result), SafeGetAttrNode::class);
    }

    public function testEnterNodeCopiesNodesAndAttributes(): void
    {
        $objectNode = new ConstantExpression('obj', 1);
        $attributeNode = new ConstantExpression('field', 1);
        $argumentsNode = new Node();

        $node = new GetAttrNode(
            ['node' => $objectNode, 'attribute' => $attributeNode, 'arguments' => $argumentsNode],
            ['type' => Template::METHOD_CALL, 'ignore_strict_check' => true, 'optimizable' => false],
            42,
            'some_tag'
        );

        $result = $this->visitor->enterNode($node, $this->env);

        self::assertInstanceOf(SafeGetAttrNode::class, $result);
        self::assertSame($objectNode, $result->getNode('node'));
        self::assertSame($attributeNode, $result->getNode('attribute'));
        self::assertSame($argumentsNode, $result->getNode('arguments'));
        self::assertSame(Template::METHOD_CALL, $result->getAttribute('type'));
        self::assertTrue($result->getAttribute('ignore_strict_check'));
        self::assertFalse($result->getAttribute('optimizable'));
        self::assertSame(42, $result->getTemplateLine());
    }

    public function testEnterNodeCopiesDefinedTestFlagWhenEnabled(): void
    {
        $this->getAttrNode->enableDefinedTest();

        $result = $this->visitor->enterNode($this->getAttrNode, $this->env);

        self::assertInstanceOf(SafeGetAttrNode::class, $result);
        self::assertTrue($result->isDefinedTestEnabled());
    }

    public function testEnterNodeDoesNotEnableDefinedTestWhenNotSet(): void
    {
        $result = $this->visitor->enterNode($this->getAttrNode, $this->env);

        self::assertInstanceOf(SafeGetAttrNode::class, $result);
        self::assertFalse($result->isDefinedTestEnabled());
    }

    public function testEnterNodeWorksWithoutOptionalArgumentsNode(): void
    {
        $node = new GetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
                // no 'arguments' node
            ],
            ['type' => Template::ANY_CALL, 'ignore_strict_check' => false, 'optimizable' => true],
            1
        );

        $result = $this->visitor->enterNode($node, $this->env);

        self::assertInstanceOf(SafeGetAttrNode::class, $result);
        self::assertFalse($result->hasNode('arguments'));
    }
}

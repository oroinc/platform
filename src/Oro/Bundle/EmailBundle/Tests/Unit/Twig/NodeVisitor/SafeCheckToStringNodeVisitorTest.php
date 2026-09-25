<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\NodeVisitor;

use Oro\Bundle\EmailBundle\Twig\Node\SafeCheckToStringNode;
use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeCheckToStringNodeVisitor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Node\CheckToStringNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;

final class SafeCheckToStringNodeVisitorTest extends TestCase
{
    private const int TEMPLATE_LINE = 42;
    private const string CONSTANT_VALUE = 'sample';

    private SafeCheckToStringNodeVisitor $visitor;
    private Environment&MockObject $env;

    #[\Override]
    protected function setUp(): void
    {
        $this->visitor = new SafeCheckToStringNodeVisitor();
        $this->env = $this->createMock(Environment::class);
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
     * SafeCheckToStringNode is itself a subclass of CheckToStringNode and must not be replaced again
     * to avoid infinite loops and double-wrapping.
     */
    public function testEnterNodeDoesNotReplaceSubclassOfCheckToStringNode(): void
    {
        $node = new SafeCheckToStringNode(new ConstantExpression(self::CONSTANT_VALUE, self::TEMPLATE_LINE));

        self::assertSame($node, $this->visitor->enterNode($node, $this->env));
    }

    public function testEnterNodeReplacesExactCheckToStringNodeWithSafeCheckToStringNode(): void
    {
        $expression = new ConstantExpression(self::CONSTANT_VALUE, self::TEMPLATE_LINE);
        $node = new CheckToStringNode($expression);

        $result = $this->visitor->enterNode($node, $this->env);

        self::assertSame(SafeCheckToStringNode::class, get_class($result));
        self::assertSame($expression, $result->getNode('expr'));
        self::assertFalse($result->getAttribute('spread'));
        self::assertSame(self::TEMPLATE_LINE, $result->getTemplateLine());
    }

    public function testEnterNodeCopiesSpreadAttribute(): void
    {
        $node = new CheckToStringNode(new ConstantExpression(self::CONSTANT_VALUE, self::TEMPLATE_LINE), true);

        $result = $this->visitor->enterNode($node, $this->env);

        self::assertSame(SafeCheckToStringNode::class, get_class($result));
        self::assertTrue($result->getAttribute('spread'));
    }
}

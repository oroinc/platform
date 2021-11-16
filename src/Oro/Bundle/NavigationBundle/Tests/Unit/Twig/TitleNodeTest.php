<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\TitleExtension;
use Oro\Bundle\NavigationBundle\Twig\TitleNode;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

class TitleNodeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Node|\PHPUnit\Framework\MockObject\MockObject */
    private $node;

    /** @var Compiler|\PHPUnit\Framework\MockObject\MockObject */
    private $compiler;

    /** @var TitleNode */
    private $titleNode;

    protected function setUp(): void
    {
        $this->node = $this->createMock(Node::class);
        $this->compiler = $this->createMock(Compiler::class);

        $this->titleNode = new TitleNode($this->node);
    }

    public function testFailedCompile()
    {
        $this->expectException(SyntaxError::class);

        $this->node->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $this->titleNode->compile($this->compiler);
    }

    public function testSuccessCompile()
    {
        $expr = $this->createMock(ArrayExpression::class);

        $this->node->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$expr]));

        $this->compiler->expects($this->exactly(2))
            ->method('raw')
            ->withConsecutive(
                ["\n"],
                [");\n"]
            )
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('write')
            ->with('$this->env->getExtension("' . TitleExtension::class . '")->set(')
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('subcompile')
            ->with($expr)
            ->willReturnSelf();

        $this->titleNode->compile($this->compiler);
    }
}

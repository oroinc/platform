<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\TitleExtension;
use Oro\Bundle\NavigationBundle\Twig\TitleNode;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

class TitleNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Node|\PHPUnit\Framework\MockObject\MockObject
     */
    private $node;

    /**
     * @var Compiler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $compiler;

    /**
     * @var TitleNode
     */
    private $titleNode;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->node = $this->createMock(Node::class);
        $this->compiler = $this->createMock(Compiler::class);

        $this->titleNode = new TitleNode($this->node);
    }

    /**
     * Tests error in twig tag call
     */
    public function testFailedCompile()
    {
        $this->expectException(\Twig\Error\SyntaxError::class);
        $this->node->expects($this->once())->method('getIterator')->willReturn([]);

        $this->titleNode->compile($this->compiler);
    }

    /**
     * Tests success node compiling
     */
    public function testSuccessCompile()
    {
        $exprMock = $this->getMockBuilder(ArrayExpression::class)->disableOriginalConstructor()->getMock();

        $this->node->expects($this->once())
            ->method('getIterator')
            ->willReturn([$exprMock]);

        $this->compiler->expects($this->at(0))
            ->method('raw')
            ->with("\n")
            ->will($this->returnSelf());

        $this->compiler->expects($this->at(1))
            ->method('write')
            ->with('$this->env->getExtension("' . TitleExtension::class . '")->set(')
            ->will($this->returnSelf());

        $this->compiler->expects($this->at(2))
            ->method('subcompile')
            ->with($exprMock)
            ->will($this->returnSelf());

        $this->compiler->expects($this->at(3))
            ->method('raw')
            ->with(");\n")
            ->will($this->returnSelf());

        $this->titleNode->compile($this->compiler);
    }
}

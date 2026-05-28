<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\TitleExtension;
use Oro\Bundle\NavigationBundle\Twig\TitleNode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

class TitleNodeTest extends TestCase
{
    /** @var Compiler|MockObject */
    private $compiler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->compiler = $this->createMock(Compiler::class);
    }

    public function testFailedCompile()
    {
        $this->expectException(SyntaxError::class);

        $node = $this->createMock(Node::class);
        $titleNode = new TitleNode($node);
        $titleNode->compile($this->compiler);
    }

    public function testSuccessCompile()
    {
        $expr = $this->createMock(ArrayExpression::class);
        $titleNode = new TitleNode($expr);

        $this->compiler->expects($this->exactly(2))
            ->method('raw')
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('write')
            ->with('$this->env->getExtension("' . TitleExtension::class . '")->set(')
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('subcompile')
            ->with($expr)
            ->willReturnSelf();

        $titleNode->compile($this->compiler);
    }
}

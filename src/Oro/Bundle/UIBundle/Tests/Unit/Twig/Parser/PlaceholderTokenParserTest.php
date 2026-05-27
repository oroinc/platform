<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\DefaultFilter;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;

class PlaceholderTokenParserTest extends \PHPUnit\Framework\TestCase
{
    private TokenStream $stream;

    /** @var Compiler|\PHPUnit\Framework\MockObject\MockObject */
    private $compiler;

    private AbstractExpression $parsedExpression;

    private PlaceholderTokenParser $tokenParser;

    #[\Override]
    protected function setUp(): void
    {
        $this->stream = new TokenStream([]);
        $this->parsedExpression = $this->createMock(AbstractExpression::class);

        $parser = $this->createMock(Parser::class);
        $parser->expects($this->any())
            ->method('getStream')
            ->willReturn($this->stream);
        $parser->expects($this->any())
            ->method('parseExpression')
            ->willReturn($this->parsedExpression);

        $this->compiler = $this->createMock(Compiler::class);

        $this->tokenParser = new PlaceholderTokenParser();
        $this->tokenParser->setParser($parser);
    }

    public function testParseSimpleNameWithoutVariables(): void
    {
        $tokenLine = 1;
        $tokenValue = 'with';
        $endToken = new Token(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$endToken, $endToken]);
        $actualNode = $this->tokenParser->parse($endToken);
        $expectedExpr = new FunctionExpression(
            'placeholder',
            new Node(['name' => $this->parsedExpression, 'variables' => new ConstantExpression([], $tokenLine)]),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertInstanceOf(PrintNode::class, $actualNode);
        $this->assertEquals($tokenLine, $actualNode->getTemplateLine());
    }

    public function testParseExpressionNameWithVariables(): void
    {
        $tokenLine = 2;
        $tokenValue = 'with';
        $nameTypeToken = new Token(Token::NAME_TYPE, $tokenValue, $tokenLine);
        $blockEndTypeToken = new Token(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$nameTypeToken, $nameTypeToken, $blockEndTypeToken, $blockEndTypeToken]);
        $actualNode = $this->tokenParser->parse($nameTypeToken);
        $contextVar = new ContextVariable($tokenValue, $tokenLine);
        $contextVar->setAttribute('ignore_strict_check', true);
        $expectedNameExpr = new DefaultFilter(
            $contextVar,
            new ConstantExpression('default', $tokenLine),
            new Node([new ConstantExpression($tokenValue, $tokenLine)], [], $tokenLine),
            $tokenLine
        );
        $expectedExpr = new FunctionExpression(
            'placeholder',
            new Node(['name' => $expectedNameExpr, 'variables' => $this->parsedExpression]),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertInstanceOf(PrintNode::class, $actualNode);
        $this->assertEquals($tokenLine, $actualNode->getTemplateLine());
    }

    private function prepareCompiler(PrintNode $node, $expr): void
    {
        $this->compiler->expects($this->once())
            ->method('addDebugInfo')
            ->with($this->identicalTo($node))
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('write')
            ->with('yield ')
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('subcompile')
            ->with($expr)
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('raw')
            ->with(";\n")
            ->willReturnSelf();
    }
}

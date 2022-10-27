<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;
use Twig\Compiler;
use Twig\ExpressionParser;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\DefaultFilter;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;

class PlaceholderTokenParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStream */
    private $stream;

    /** @var Compiler|\PHPUnit\Framework\MockObject\MockObject */
    private $compiler;

    /** @var PlaceholderTokenParser */
    private $tokenParser;

    protected function setUp(): void
    {
        $this->stream = new TokenStream([]);

        $expressionParser = $this->createMock(ExpressionParser::class);
        $expressionParser->expects($this->any())
            ->method('parseExpression')
            ->willReturn($this->createMock(AbstractExpression::class));

        $parser = $this->createMock(Parser::class);
        $parser->expects($this->any())
            ->method('getStream')
            ->willReturn($this->stream);
        $parser->expects($this->any())
            ->method('getExpressionParser')
            ->willReturn($expressionParser);

        $this->compiler = $this->createMock(Compiler::class);

        $this->tokenParser = new PlaceholderTokenParser();
        $this->tokenParser->setParser($parser);
    }

    public function testParseSimpleNameWithoutVariables(): void
    {
        $expr = $this->createMock(AbstractExpression::class);
        $tokenLine = 1;
        $tokenValue = 'with';
        $endToken = new Token(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$endToken, $endToken]);
        $actualNode = $this->tokenParser->parse($endToken);
        $expectedExpr = new FunctionExpression(
            'placeholder',
            new Node(['name' => $expr, 'variables' => new ConstantExpression([], $tokenLine)]),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertInstanceOf(PrintNode::class, $actualNode);
        $this->assertEquals($tokenLine, $actualNode->getTemplateLine());
        $this->assertEquals('placeholder', $actualNode->getNodeTag());
    }

    public function testParseExpressionNameWithVariables(): void
    {
        $expr = $this->createMock(AbstractExpression::class);
        $tokenLine = 2;
        $tokenValue = 'with';
        $nameTypeToken = new Token(Token::NAME_TYPE, $tokenValue, $tokenLine);
        $blockEndTypeToken = new Token(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$nameTypeToken, $nameTypeToken, $blockEndTypeToken, $blockEndTypeToken]);
        $actualNode = $this->tokenParser->parse($nameTypeToken);
        $expectedNameExpr = new DefaultFilter(
            new NameExpression($tokenValue, $tokenLine),
            new ConstantExpression('default', $tokenLine),
            new Node([new ConstantExpression($tokenValue, $tokenLine)], [], $tokenLine),
            $tokenLine
        );
        $expectedExpr = new FunctionExpression(
            'placeholder',
            new Node(['name' => $expectedNameExpr, 'variables' => $expr]),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertInstanceOf(PrintNode::class, $actualNode);
        $this->assertEquals($tokenLine, $actualNode->getTemplateLine());
        $this->assertEquals('placeholder', $actualNode->getNodeTag());
    }

    private function prepareCompiler(PrintNode $node, $expr): void
    {
        $this->compiler->expects($this->once())
            ->method('addDebugInfo')
            ->with($this->identicalTo($node))
            ->willReturnSelf();
        $this->compiler->expects($this->once())
            ->method('write')
            ->with('echo ')
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

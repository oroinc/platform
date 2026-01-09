<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Environment;
use Twig\ExpressionParser;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;
use Twig\TwigFunction;

class PlaceholderTokenParserTest extends TestCase
{
    private TokenStream $stream;
    private Compiler&MockObject $compiler;
    private PlaceholderTokenParser $tokenParser;
    private AbstractExpression&MockObject $parsedExpr;
    private TwigFunction $placeholderFunction;

    #[\Override]
    protected function setUp(): void
    {
        $this->stream = new TokenStream([]);

        $this->parsedExpr = $this->createMock(AbstractExpression::class);

        $expressionParser = $this->createMock(ExpressionParser::class);
        $expressionParser->expects($this->any())
            ->method('parseExpression')
            ->willReturn($this->parsedExpr);

        $this->placeholderFunction = new TwigFunction('placeholder');

        $env = new Environment(new ArrayLoader());
        $env->addFunction($this->placeholderFunction);

        $parser = $this->createMock(Parser::class);
        $parser->expects($this->any())
            ->method('getStream')
            ->willReturn($this->stream);
        $parser->expects($this->any())
            ->method('getExpressionParser')
            ->willReturn($expressionParser);
        $parser->expects($this->any())
            ->method('getEnvironment')
            ->willReturn($env);

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
            $this->placeholderFunction,
            new Node([$this->parsedExpr, new ConstantExpression([], $tokenLine)]),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertInstanceOf(PrintNode::class, $actualNode);
        $this->assertEquals($tokenLine, $actualNode->getTemplateLine());
        $this->assertInstanceOf(FunctionExpression::class, $actualNode->getNode('expr'));
        $this->assertEquals('placeholder', $actualNode->getNode('expr')->getAttribute('name'));
    }

    public function testParseExpressionNameWithVariables(): void
    {
        $tokenLine = 2;
        $tokenValue = 'with';
        $nameTypeToken = new Token(Token::NAME_TYPE, $tokenValue, $tokenLine);
        $blockEndTypeToken = new Token(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$nameTypeToken, $nameTypeToken, $blockEndTypeToken, $blockEndTypeToken]);
        $actualNode = $this->tokenParser->parse($nameTypeToken);
        $expectedNameExpr = new ConstantExpression($tokenValue, $tokenLine);
        $expectedExpr = new FunctionExpression(
            $this->placeholderFunction,
            new Node([$expectedNameExpr, $this->parsedExpr]),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertInstanceOf(PrintNode::class, $actualNode);
        $this->assertEquals($tokenLine, $actualNode->getTemplateLine());
        $this->assertInstanceOf(FunctionExpression::class, $actualNode->getNode('expr'));
        $this->assertEquals('placeholder', $actualNode->getNode('expr')->getAttribute('name'));
    }

    private function prepareCompiler(PrintNode $node, FunctionExpression $expr): void
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

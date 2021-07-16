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
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Parser
     */
    private $parser;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExpressionParser
     */
    private $expressionParser;

    /**
     * @var TokenStream
     */
    private $stream;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Compiler
     */
    private $compiler;

    /**
     * @var PlaceholderTokenParser
     */
    private $tokenParser;

    protected function setUp(): void
    {
        $this->stream = new TokenStream([]);
        $this->expressionParser = $this->createMock(ExpressionParser::class);
        $this->parser = $this->createMock(Parser::class);
        $this->parser->expects($this->any())
            ->method('getStream')
            ->willReturn($this->stream);
        $this->parser->expects($this->any())
            ->method('getExpressionParser')
            ->willReturn($this->expressionParser);

        $this->expressionParser->expects($this->any())
            ->method('parseExpression')
            ->willReturn($this->createExpressionNode());

        $this->compiler = $this->createMock(Compiler::class);
        $this->tokenParser = new PlaceholderTokenParser();
        $this->tokenParser->setParser($this->parser);
    }

    public function testParseSimpleNameWithoutVariables(): void
    {
        $expr = $this->createExpressionNode();
        $tokenLine = 1;
        $tokenValue = 'with';

        $endToken = $this->createToken(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$endToken, $endToken]);
        $actualNode = $this->tokenParser->parse($endToken);

        $expectedExpr = new FunctionExpression(
            'placeholder',
            new Node(['name' => $expr, 'variables' => new ConstantExpression([], $tokenLine)]),
            $tokenLine
        );
        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);

        $this->assertNode($actualNode, $tokenLine);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testParseExpressionNameWithVariables(): void
    {
        $expr = $this->createExpressionNode();
        $tokenLine = 2;
        $tokenValue = 'with';
        $nameTypeToken = $this->createToken(Token::NAME_TYPE, $tokenValue, $tokenLine);
        $blockEndTypeToken = $this->createToken(Token::BLOCK_END_TYPE, $tokenValue, $tokenLine);
        $this->stream->injectTokens([$nameTypeToken, $nameTypeToken, $blockEndTypeToken, $blockEndTypeToken]);
        $actualNode = $this->tokenParser->parse($nameTypeToken);
        $expectedNameExpr = new DefaultFilter(
            new NameExpression($tokenValue, $tokenLine),
            new ConstantExpression('default', $tokenLine),
            new Node(
                [
                    new ConstantExpression(
                        $tokenValue,
                        $tokenLine
                    )
                ],
                [],
                $tokenLine
            ),
            $tokenLine
        );
        $expectedExpr = new FunctionExpression(
            'placeholder',
            new Node(
                [
                    'name' => $expectedNameExpr,
                    'variables' => $expr
                ]
            ),
            $tokenLine
        );

        $this->prepareCompiler($actualNode, $expectedExpr);
        $actualNode->compile($this->compiler);
        $this->assertNode($actualNode, $tokenLine);
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

    private function assertNode(PrintNode $node, int $tokenLine): void
    {
        $this->assertInstanceOf(PrintNode::class, $node);
        $this->assertEquals($tokenLine, $node->getTemplateLine());
        $this->assertEquals('placeholder', $node->getNodeTag());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createExpressionNode()
    {
        return $this->createMock(AbstractExpression::class);
    }

    /**
     * @param int $type
     * @param string $value
     * @param int $lineno
     *
     * @return Token
     */
    private function createToken($type = Token::TEXT_TYPE, $value = 'with', $lineno = 1): Token
    {
        return new Token($type, $value, $lineno);
    }
}

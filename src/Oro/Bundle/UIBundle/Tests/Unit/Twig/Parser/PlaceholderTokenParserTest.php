<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;

class PlaceholderTokenParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $parser;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $expressionParser;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stream;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $compiler;

    /**
     * @var PlaceholderTokenParser
     */
    private $tokenParser;

    protected function setUp()
    {
        $this->stream = $this->createMock('Twig_TokenStream');
        $this->expressionParser = $this->createMock('Twig_ExpressionParser');
        $this->parser = $this->createMock('Twig_Parser');

        $this->parser->expects($this->any())
            ->method('getStream')
            ->willReturn($this->stream);
        $this->parser->expects($this->any())
            ->method('getExpressionParser')
            ->willReturn($this->expressionParser);

        $this->compiler = $this->getMockBuilder('Twig_Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenParser = new PlaceholderTokenParser();
        $this->tokenParser->setParser($this->parser);
    }

    public function testParseSimpleNameWithoutVariables()
    {
        $expectedLine = 101;

        $nameExpr = $this->createExpressionNode();

        $token = $this->createToken();
        $token->expects($this->any())
            ->method('getLine')
            ->willReturn($expectedLine);

        $this->stream->expects($this->at(0))
            ->method('test')
            ->with(\Twig_Token::NAME_TYPE)
            ->willReturn(false);

        $this->expressionParser->expects($this->once())
            ->method('parseExpression')
            ->willReturn($nameExpr);

        $this->stream->expects($this->at(1))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'with')
            ->willReturn(false);

        $this->stream->expects($this->at(2))
            ->method('expect')
            ->with(\Twig_Token::BLOCK_END_TYPE);

        $actualNode = $this->tokenParser->parse($token);
        $this->assertInstanceOf('\Twig_Node_Print', $actualNode);
        $this->assertEquals($expectedLine, $actualNode->getTemplateLine());
        $this->assertEquals('placeholder', $actualNode->getNodeTag());

        $expectedExpr           = new \Twig_Node_Expression_Function(
            'placeholder',
            new \Twig_Node(
                array(
                    'name'       => $nameExpr,
                    'variables'  => new \Twig_Node_Expression_Constant(array(), $expectedLine)
                )
            ),
            $expectedLine
        );
        $this->compiler->expects($this->once())
            ->method('addDebugInfo')
            ->with($this->identicalTo($actualNode))
            ->will($this->returnSelf());
        $this->compiler->expects($this->once())
            ->method('write')
            ->with('echo ')
            ->will($this->returnSelf());
        $this->compiler->expects($this->once())
            ->method('subcompile')
            ->with($expectedExpr)
            ->will($this->returnSelf());
        $this->compiler->expects($this->once())
            ->method('raw')
            ->with(";\n")
            ->will($this->returnSelf());
        $actualNode->compile($this->compiler);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testParseExpressionNameWithVariables()
    {
        $expectedLine  = 101;
        $variablesExpr = $this->createExpressionNode();

        $token = $this->createToken();
        $token->expects($this->any())
            ->method('getLine')
            ->willReturn($expectedLine);

        $nameToken      = $this->createToken();
        $nameTokenValue = 'nameTokenValue';
        $nameTokenLine  = 102;
        $nameToken->expects($this->once())
            ->method('getValue')
            ->willReturn($nameTokenValue);
        $nameToken->expects($this->once())
            ->method('getLine')
            ->willReturn($nameTokenLine);

        $this->stream->expects($this->at(0))
            ->method('test')
            ->with(\Twig_Token::NAME_TYPE)
            ->willReturn(true);

        $this->stream->expects($this->at(1))
            ->method('getCurrent')
            ->willReturn($nameToken);

        $this->stream->expects($this->at(2))
            ->method('next');

        $this->stream->expects($this->at(3))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'with')
            ->willReturn(true);

        $this->expressionParser->expects($this->once())
            ->method('parseExpression')
            ->willReturn($variablesExpr);

        $this->stream->expects($this->at(4))
            ->method('expect')
            ->with(\Twig_Token::BLOCK_END_TYPE);

        $actualNode = $this->tokenParser->parse($token);
        $this->assertInstanceOf('\Twig_Node_Print', $actualNode);
        $this->assertEquals($expectedLine, $actualNode->getTemplateLine());
        $this->assertEquals('placeholder', $actualNode->getNodeTag());

        $expectedNameExpr       = new \Twig_Node_Expression_Filter_Default(
            new \Twig_Node_Expression_Name($nameTokenValue, $nameTokenLine),
            new \Twig_Node_Expression_Constant('default', $nameTokenLine),
            new \Twig_Node(
                array(
                    new \Twig_Node_Expression_Constant(
                        $nameTokenValue,
                        $nameTokenLine
                    )
                ),
                array(),
                $nameTokenLine
            ),
            $nameTokenLine
        );
        $expectedExpr           = new \Twig_Node_Expression_Function(
            'placeholder',
            new \Twig_Node(
                array(
                    'name'       => $expectedNameExpr,
                    'variables'  => $variablesExpr
                )
            ),
            $expectedLine
        );
        $this->compiler->expects($this->once())
            ->method('addDebugInfo')
            ->with($this->identicalTo($actualNode))
            ->will($this->returnSelf());
        $this->compiler->expects($this->once())
            ->method('write')
            ->with('echo ')
            ->will($this->returnSelf());
        $this->compiler->expects($this->once())
            ->method('subcompile')
            ->with($expectedExpr)
            ->will($this->returnSelf());
        $this->compiler->expects($this->once())
            ->method('raw')
            ->with(";\n")
            ->will($this->returnSelf());
        $actualNode->compile($this->compiler);
    }

    private function createExpressionNode()
    {
        return $this->createMock('Twig_Node_Expression');
    }

    private function createToken()
    {
        return $this->createMock('Twig_Token');
    }
}

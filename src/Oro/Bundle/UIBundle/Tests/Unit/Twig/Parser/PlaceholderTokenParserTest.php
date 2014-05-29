<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;

class PlaceholderTokenParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $expressionParser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stream;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $compiler;

    /**
     * @var PlaceholderTokenParser
     */
    protected $tokenParser;

    protected function setUp()
    {
        $this->stream = $this->getMockBuilder('Twig_TokenStream')
            ->disableOriginalConstructor()
            ->getMock();

        $this->expressionParser = $this->getMockBuilder('Twig_ExpressionParser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('Twig_Parser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser->expects($this->any())
            ->method('getStream')
            ->will($this->returnValue($this->stream));
        $this->parser->expects($this->any())
            ->method('getExpressionParser')
            ->will($this->returnValue($this->expressionParser));

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
            ->will($this->returnValue($expectedLine));

        $this->stream->expects($this->at(0))
            ->method('test')
            ->with(\Twig_Token::NAME_TYPE)
            ->will($this->returnValue(false));

        $this->expressionParser->expects($this->once())
            ->method('parseExpression')
            ->will($this->returnValue($nameExpr));

        $this->stream->expects($this->at(1))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'with')
            ->will($this->returnValue(false));

        $this->stream->expects($this->at(2))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'set')
            ->will($this->returnValue(false));

        $this->stream->expects($this->at(3))
            ->method('expect')
            ->with(\Twig_Token::BLOCK_END_TYPE);

        $actualNode = $this->tokenParser->parse($token);
        $this->assertInstanceOf('\Twig_Node_Print', $actualNode);
        $this->assertEquals($expectedLine, $actualNode->getLine());
        $this->assertEquals('placeholder', $actualNode->getNodeTag());

        $expectedAttributesExpr = new \Twig_Node_Expression_Array(
            array(),
            $expectedLine
        );
        $expectedExpr           = new \Twig_Node_Expression_Function(
            'placeholder',
            new \Twig_Node(
                array(
                    'name'       => $nameExpr,
                    'variables'  => new \Twig_Node_Expression_Constant(array(), $expectedLine),
                    'attributes' => $expectedAttributesExpr
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
            ->will($this->returnValue($expectedLine));

        $nameToken      = $this->createToken();
        $nameTokenValue = 'nameTokenValue';
        $nameTokenLine  = 102;
        $nameToken->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($nameTokenValue));
        $nameToken->expects($this->once())
            ->method('getLine')
            ->will($this->returnValue($nameTokenLine));

        $this->stream->expects($this->at(0))
            ->method('test')
            ->with(\Twig_Token::NAME_TYPE)
            ->will($this->returnValue(true));

        $this->stream->expects($this->at(1))
            ->method('getCurrent')
            ->will($this->returnValue($nameToken));

        $this->stream->expects($this->at(2))
            ->method('next');

        $this->stream->expects($this->at(3))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'with')
            ->will($this->returnValue(true));

        $this->expressionParser->expects($this->once())
            ->method('parseExpression')
            ->will($this->returnValue($variablesExpr));

        $this->stream->expects($this->at(4))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'set')
            ->will($this->returnValue(true));

        $this->stream->expects($this->at(5))
            ->method('expect')
            ->with(\Twig_Token::NAME_TYPE)
            ->will($this->returnValue(new \Twig_Token(\Twig_Token::NAME_TYPE, 'testVar', $expectedLine)));

        $this->stream->expects($this->at(6))
            ->method('expect')
            ->with(\Twig_Token::BLOCK_END_TYPE);

        $actualNode = $this->tokenParser->parse($token);
        $this->assertInstanceOf('\Twig_Node_Set', $actualNode);
        $this->assertEquals($expectedLine, $actualNode->getLine());
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
        $expectedAttributesExpr = new \Twig_Node_Expression_Array(
            array(
                new \Twig_Node_Expression_Constant('output', 0),
                new \Twig_Node_Expression_Constant('raw', 0)
            ),
            $expectedLine
        );
        $expectedExpr           = new \Twig_Node_Expression_Function(
            'placeholder',
            new \Twig_Node(
                array(
                    'name'       => $expectedNameExpr,
                    'variables'  => $variablesExpr,
                    'attributes' => $expectedAttributesExpr
                )
            ),
            $expectedLine
        );
        $expectedNameExpr       = new \Twig_Node(
            array(new \Twig_Node_Expression_AssignName('testVar', 0))
        );
        $expectedValueExpr      = new \Twig_Node(
            array($expectedExpr)
        );
        $this->compiler->expects($this->at(0))
            ->method('addDebugInfo')
            ->with($this->identicalTo($actualNode))
            ->will($this->returnSelf());
        $this->compiler->expects($this->at(1))
            ->method('subcompile')
            ->with($expectedNameExpr)
            ->will($this->returnSelf());
        $this->compiler->expects($this->at(2))
            ->method('raw')
            ->with(' = ')
            ->will($this->returnSelf());
        $this->compiler->expects($this->at(3))
            ->method('subcompile')
            ->with($expectedValueExpr)
            ->will($this->returnSelf());
        $this->compiler->expects($this->at(4))
            ->method('raw')
            ->with(";\n")
            ->will($this->returnSelf());
        $actualNode->compile($this->compiler);
    }

    protected function createExpressionNode()
    {
        return $this->getMockBuilder('Twig_Node_Expression')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createToken()
    {
        return $this->getMockBuilder('Twig_Token')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

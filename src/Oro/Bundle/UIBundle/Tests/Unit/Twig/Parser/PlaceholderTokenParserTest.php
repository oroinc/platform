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
     * @var PlaceholderTokenParser
     */
    protected $tokenParser;

    public function setUp()
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

        $this->tokenParser = new PlaceholderTokenParser();
        $this->tokenParser->setParser($this->parser);
    }

    public function testParseSimpleNameWithoutVariables()
    {
        $expectedLine = 101;
        $expectedNameExpression = $this->createExpressionNode();
        $expectedVariablesExpression = new \Twig_Node_Expression_Constant(array(), $expectedLine);

        $actualToken = $this->createToken();

        $this->stream->expects($this->at(0))
            ->method('test')
            ->with(\Twig_Token::NAME_TYPE)
            ->will($this->returnValue(false));

        $this->expressionParser->expects($this->once())
            ->method('parseExpression')
            ->will($this->returnValue($expectedNameExpression));

        $actualToken->expects($this->atLeastOnce())
            ->method('getLine')
            ->will($this->returnValue($expectedLine));

        $this->stream->expects($this->at(1))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'with')
            ->will($this->returnValue(false));

        $this->stream->expects($this->at(2))
            ->method('expect')
            ->with(\Twig_Token::BLOCK_END_TYPE);

        $actualNode = $this->tokenParser->parse($actualToken);
        $this->assertInstanceOf('Oro\\Bundle\\UIBundle\\Twig\\Node\\PlaceholderNode', $actualNode);

        $this->assertAttributeEquals($expectedLine, 'lineno', $actualNode);
        $this->assertAttributeEquals('placeholder', 'tag', $actualNode);
        $this->assertAttributeEquals($expectedNameExpression, 'nameNode', $actualNode);
        $this->assertAttributeEquals($expectedVariablesExpression, 'variablesNode', $actualNode);
    }

    public function testParseExpressionNameWithVariables()
    {
        $expectedLine = 101;
        $expectedVariablesExpression = $this->createExpressionNode();

        $actualToken = $this->createToken();

        $this->stream->expects($this->at(0))
            ->method('test')
            ->with(\Twig_Token::NAME_TYPE)
            ->will($this->returnValue(true));

        $nameToken = $this->createToken();

        $this->stream->expects($this->at(1))
            ->method('getCurrent')
            ->will($this->returnValue($nameToken));

        $nameTokenValue = 'nameTokenValue';
        $nameTokenLine = 102;
        $nameToken->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($nameTokenValue));

        $nameToken->expects($this->once())
            ->method('getLine')
            ->will($this->returnValue($nameTokenLine));

        $this->stream->expects($this->at(2))
            ->method('next');

        $this->stream->expects($this->at(3))
            ->method('nextIf')
            ->with(\Twig_Token::NAME_TYPE, 'with')
            ->will($this->returnValue(true));

        $this->expressionParser->expects($this->once())
            ->method('parseExpression')
            ->will($this->returnValue($expectedVariablesExpression));

        $this->stream->expects($this->at(4))
            ->method('expect')
            ->with(\Twig_Token::BLOCK_END_TYPE);

        $actualToken->expects($this->atLeastOnce())
            ->method('getLine')
            ->will($this->returnValue($expectedLine));

        $actualNode = $this->tokenParser->parse($actualToken);
        $this->assertInstanceOf('Oro\\Bundle\\UIBundle\\Twig\\Node\\PlaceholderNode', $actualNode);

        $this->assertAttributeEquals($expectedLine, 'lineno', $actualNode);
        $this->assertAttributeEquals('placeholder', 'tag', $actualNode);
        $this->assertAttributeInstanceOf('Twig_Node_Expression_Filter_Default', 'nameNode', $actualNode);
        $this->assertAttributeEquals($expectedVariablesExpression, 'variablesNode', $actualNode);
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

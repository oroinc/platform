<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;

class PlaceholderTokenParserTest extends \PHPUnit_Framework_TestCase
{
    const LINE = 12;

    /**
     * @var \Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser
     */
    private $placeholder;

    public function setUp()
    {
        $this->placeholder = new PlaceholderTokenParser(
            [
                'test_position' => [
                    'items' => [
                        'test_item' => []
                    ]
                ]
            ],
            'test_class'
        );
    }

    /**
     * @param \Twig_TokenStream $stream
     *
     * @dataProvider streamProvider
     */
    public function testParse($stream, $getExpressionParser, $parseExpression)
    {
        $startToken = new \Twig_Token(\Twig_Token::NAME_TYPE, 'placeholder', self::LINE);

        $expressionParser = $this->getMockBuilder('\Twig_ExpressionParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser = $this->getMockBuilder('\Twig_Parser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('getStream')
            ->will($this->returnValue($stream));

        $parser->expects($this->$getExpressionParser())
            ->method('getExpressionParser')
            ->will($this->returnValue($expressionParser));

        $expressionParser->expects($this->$parseExpression())
            ->method('parseExpression')
            ->will($this->returnValue(null));

        $this->placeholder->setParser($parser);

        $resultNode = $this->placeholder->parse($startToken);

        $this->assertEquals(self::LINE, $resultNode->getLine());
        $this->assertEquals('placeholder', $resultNode->getNodeTag());
    }

    public function streamProvider()
    {
        return [
            'name' => [
                'stream' => new \Twig_TokenStream(
                    [
                        new \Twig_Token(\Twig_Token::NAME_TYPE, 'test_position', self::LINE),
                        new \Twig_Token(\Twig_Token::NAME_TYPE, 'with', self::LINE),
                        new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', self::LINE),
                        new \Twig_Token(\Twig_Token::EOF_TYPE, '', self::LINE),
                    ]
                ),
                'getExpressionParser' => 'once',
                'parseExpression' => 'once'
            ],
            'string' => [
                'stream' => new \Twig_TokenStream(
                    [
                        new \Twig_Token(\Twig_Token::STRING_TYPE, 'test_position', self::LINE),
                        new \Twig_Token(\Twig_Token::NAME_TYPE, 'with', self::LINE),
                        new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', self::LINE),
                        new \Twig_Token(\Twig_Token::EOF_TYPE, '', self::LINE),
                    ]
                ),
                'getExpressionParser' => 'once',
                'parseExpression' => 'once'
            ],
            'concat' => [
                'stream' => new \Twig_TokenStream(
                    [
                        new \Twig_Token(\Twig_Token::STRING_TYPE, 'test', self::LINE),
                        new \Twig_Token(\Twig_Token::OPERATOR_TYPE, '~', self::LINE),
                        new \Twig_Token(\Twig_Token::STRING_TYPE, '_position', self::LINE),
                        new \Twig_Token(\Twig_Token::NAME_TYPE, 'with', self::LINE),
                        new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', self::LINE),
                        new \Twig_Token(\Twig_Token::EOF_TYPE, '', self::LINE),
                    ]
                ),
                'getExpressionParser' => 'once',
                'parseExpression' => 'once'
            ],
            'noparams' => [
                'stream' => new \Twig_TokenStream(
                    [
                        new \Twig_Token(\Twig_Token::STRING_TYPE, 'test_position', self::LINE),
                        new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', self::LINE),
                        new \Twig_Token(\Twig_Token::EOF_TYPE, '', self::LINE),
                    ]
                ),
                'getExpressionParser' => 'never',
                'parseExpression' => 'never'
            ]
        ];
    }
}

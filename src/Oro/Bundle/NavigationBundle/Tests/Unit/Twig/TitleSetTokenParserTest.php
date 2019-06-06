<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\TitleSetTokenParser;
use Twig\ExpressionParser;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;

class TitleSetTokenParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests token parser
     */
    public function testParsing()
    {
        $node = $this->createMock(Node::class);

        $exprParser = $this->getMockBuilder(ExpressionParser::class)
                           ->disableOriginalConstructor()
                           ->getMock();
        $exprParser->expects($this->once())
                   ->method('parseArguments')
                   ->will($this->returnValue($node));

        $stream = new TokenStream([
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]);

        $parser = $this->getMockBuilder(Parser::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        $parser->expects($this->once())
               ->method('getExpressionParser')
               ->will($this->returnValue($exprParser));
        $parser->expects($this->once())
               ->method('getStream')
               ->will($this->returnValue($stream));

        $token = new Token(Token::NAME_TYPE, 'oro_title_set', 1);
        $tokenParser = new TitleSetTokenParser();
        $tokenParser->setParser($parser);
        $tokenParser->parse($token);
    }

    /**
     * Tests tag name
     */
    public function testTagName()
    {
        $tokenParser = new TitleSetTokenParser();
        $this->assertEquals('oro_title_set', $tokenParser->getTag());
    }
}

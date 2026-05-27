<?php

declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\TitleSetTokenParser;
use PHPUnit\Framework\TestCase;
use Twig\Node\Expression\ArrayExpression;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;

class TitleSetTokenParserTest extends TestCase
{
    public function testParsing()
    {
        $expr = $this->createMock(ArrayExpression::class);

        $stream = new TokenStream([
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]);

        $parser = $this->createMock(Parser::class);
        $parser->expects($this->once())
           ->method('parseExpression')
           ->willReturn($expr);
        $parser->expects($this->once())
           ->method('getStream')
           ->willReturn($stream);

        $token = new Token(Token::NAME_TYPE, 'oro_title_set', 1);
        $tokenParser = new TitleSetTokenParser();
        $tokenParser->setParser($parser);
        $tokenParser->parse($token);
    }

    public function testTagName()
    {
        $tokenParser = new TitleSetTokenParser();
        $this->assertEquals('oro_title_set', $tokenParser->getTag());
    }
}

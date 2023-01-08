<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Expression\Date\Token;

class CompilerTest extends \PHPUnit\Framework\TestCase
{
    public function testCompile()
    {
        $expectedResult = 'Compiled String';
        $inputString = 'some test string';
        $tokens = [new Token(Token::TYPE_DATE, '2001-02-03')];

        $lexer = $this->createMock(Lexer::class);
        $lexer->expects($this->once())
            ->method('tokenize')
            ->with($inputString)
            ->willReturn($tokens);
        $parser = $this->createMock(Parser::class);
        $parser->expects($this->once())
            ->method('parse')
            ->with($tokens, false)
            ->willReturn($expectedResult);

        $compiler = new Compiler($lexer, $parser);
        $this->assertSame($expectedResult, $compiler->compile($inputString, false));
    }
}

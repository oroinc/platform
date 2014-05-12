<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\Token;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCompile()
    {
        $expectedResult = 'Compiled String';
        $inputString    = 'some test string';
        $tokens         = [new Token(Token::TYPE_DATE, '2001-02-03')];

        $lexerMock  = $this->getMockBuilder('Oro\\Bundle\\FilterBundle\\Expression\\Date\\Lexer')
            ->disableOriginalConstructor()->getMock();
        $parserMock = $this->getMockBuilder('Oro\\Bundle\\FilterBundle\\Expression\\Date\\Parser')
            ->disableOriginalConstructor()->getMock();

        $lexerMock->expects($this->once())->method('tokenize')->with($inputString)
            ->will($this->returnValue($tokens));
        $parserMock->expects($this->once())->method('parse')->with($tokens)
            ->will($this->returnValue($expectedResult));

        $compiler = new Compiler($lexerMock, $parserMock);
        $this->assertSame($expectedResult, $compiler->compile($inputString));
    }
}

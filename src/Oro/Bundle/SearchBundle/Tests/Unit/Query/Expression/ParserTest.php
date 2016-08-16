<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Expression;

use Oro\Bundle\SearchBundle\Query\Expression\Parser;
use Oro\Bundle\SearchBundle\Query\Expression\Token;
use Oro\Bundle\SearchBundle\Query\Expression\TokenStream;
use Oro\Bundle\SearchBundle\Query\Query;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParsesSelectKeyword()
    {
        $query = new Query();

        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, 'select'],
            [Token::PUNCTUATION_TYPE, '('],
            [Token::STRING_TYPE, 'test1'],
            [Token::PUNCTUATION_TYPE, ','],
            [Token::STRING_TYPE, 'test2'],
            [Token::PUNCTUATION_TYPE, ')'],
            [Token::EOF_TYPE, '']
        ]);

        $tokenStream = new TokenStream($tokens);

        $parser = new Parser($query);

        $parser->parse($tokenStream);

        $this->assertContains('text.test1', $query->getSelect());
        $this->assertContains('text.test2', $query->getSelect());
    }

    private function generateTokens($elements)
    {
        $result = [];

        foreach ($elements as $k => $element) {
            $result[] = new Token($element[0], $element[1], $k);
        }

        return $result;
    }
}

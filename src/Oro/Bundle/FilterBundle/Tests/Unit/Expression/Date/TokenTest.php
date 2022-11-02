<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Token;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    private string $testType;
    private int $testValue;
    private Token $token;

    protected function setUp(): void
    {
        $this->testType = Token::TYPE_INTEGER;
        $this->testValue = 123;

        $this->token = new Token($this->testType, $this->testValue);
    }

    public function testTokenInterface()
    {
        $this->assertSame($this->testType, $this->token->getType());
        $this->assertSame($this->testValue, $this->token->getValue());

        $this->assertTrue($this->token->is($this->testType));
        $this->assertTrue($this->token->is($this->testType, $this->testValue));

        $this->assertFalse($this->token->is(Token::TYPE_DATE));
    }
}

<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    private string $testType;
    private int $testValue;
    private Token $token;

    #[\Override]
    protected function setUp(): void
    {
        $this->testType = Token::TYPE_INTEGER;
        $this->testValue = 123;

        $this->token = new Token($this->testType, $this->testValue);
    }

    public function testTokenInterface(): void
    {
        $this->assertSame($this->testType, $this->token->getType());
        $this->assertSame($this->testValue, $this->token->getValue());

        $this->assertTrue($this->token->is($this->testType));
        $this->assertTrue($this->token->is($this->testType, $this->testValue));

        $this->assertFalse($this->token->is(Token::TYPE_DATE));
    }
}

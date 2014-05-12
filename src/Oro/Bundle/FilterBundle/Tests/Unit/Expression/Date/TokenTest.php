<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Token;

class TokenTest extends \PHPUnit_Framework_TestCase
{
    protected $testType;
    protected $testValue;

    /** @var  Token */
    protected $token;

    public function setUp()
    {
        $this->testType  = Token::TYPE_INTEGER;
        $this->testValue = 123;

        $this->token = new Token($this->testType, $this->testValue);
    }

    public function tearDown()
    {
        unset($this->token);
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

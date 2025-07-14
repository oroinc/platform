<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Generator;

use Oro\Bundle\SecurityBundle\Generator\RandomTokenGenerator;
use PHPUnit\Framework\TestCase;

class RandomTokenGeneratorTest extends TestCase
{
    private RandomTokenGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new RandomTokenGenerator();
    }

    public function testGenerateToken(): void
    {
        $token = $this->generator->generateToken();

        $this->assertTrue(ctype_print($token), 'is printable');
        $this->assertStringNotMatchesFormat('%S+%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S/%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S=%S', $token, 'is URI safe');
    }
}

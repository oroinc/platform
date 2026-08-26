<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Generator;

use Oro\Bundle\SecurityBundle\Generator\RandomTokenGenerator;
use PHPUnit\Framework\TestCase;

class RandomTokenGeneratorTest extends TestCase
{
    public function testGenerateToken(): void
    {
        $generator = new RandomTokenGenerator();
        $token = $generator->generateToken();

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertNotSame($token, $generator->generateToken());
    }

    public function testGenerateWithCustomEntropy(): void
    {
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', RandomTokenGenerator::generate(128));
    }

    public function testGenerateTokenRejectsInvalidEntropy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The token entropy must be a positive multiple of 8 bits.');

        RandomTokenGenerator::generate(7);
    }
}

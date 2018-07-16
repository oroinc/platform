<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Generator;

use Oro\Bundle\SecurityBundle\Generator\RandomTokenGenerator;

class RandomTokenGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RandomTokenGenerator
     */
    private $generator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->generator = new RandomTokenGenerator();
    }

    public function testGenerateToken()
    {
        $token = $this->generator->generateToken();

        $this->assertTrue(ctype_print($token), 'is printable');
        $this->assertStringNotMatchesFormat('%S+%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S/%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S=%S', $token, 'is URI safe');
    }
}

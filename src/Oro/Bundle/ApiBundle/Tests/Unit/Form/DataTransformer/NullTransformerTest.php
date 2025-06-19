<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NullTransformer;
use PHPUnit\Framework\TestCase;

class NullTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $value = 'test';
        self::assertSame($value, NullTransformer::getInstance()->transform($value));
    }

    public function testReverseTransform(): void
    {
        $value = 'test';
        self::assertSame($value, NullTransformer::getInstance()->reverseTransform($value));
    }
}

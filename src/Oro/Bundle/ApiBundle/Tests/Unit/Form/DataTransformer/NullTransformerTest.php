<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NullTransformer;

class NullTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransform()
    {
        $value = 'test';
        self::assertSame($value, NullTransformer::getInstance()->transform($value));
    }

    public function testReverseTransform()
    {
        $value = 'test';
        self::assertSame($value, NullTransformer::getInstance()->reverseTransform($value));
    }
}

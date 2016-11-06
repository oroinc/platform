<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DateTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NullTransformer;

class NullTransformerTest extends \PHPUnit_Framework_TestCase
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

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\BooleanToStringTransformer;

class BooleanToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $expected)
    {
        $transformer = new BooleanToStringTransformer();
        self::assertEquals($expected, $transformer->transform($value));
    }

    public function transformDataProvider()
    {
        return [
            [null, ''],
            [true, 'true'],
            [false, 'false']
        ];
    }

    public function testTransformWithInvalidValue()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->transform(1);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $transformer = new BooleanToStringTransformer();
        self::assertEquals($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        return [
            ['', null],
            ['true', true],
            ['yes', true],
            ['1', true],
            ['false', false],
            ['no', false],
            ['0', false]
        ];
    }

    public function testReverseTransformWithNotStringValue()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformWithInvalidValue()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform('test');
    }
}

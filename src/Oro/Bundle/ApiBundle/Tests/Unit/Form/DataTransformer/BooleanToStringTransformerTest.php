<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DateTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\BooleanToStringTransformer;

class BooleanToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $expected)
    {
        $transformer = new BooleanToStringTransformer();
        $this->assertEquals($expected, $transformer->transform($value));
    }

    public function transformDataProvider()
    {
        return [
            [null, ''],
            [true, 'true'],
            [false, 'false'],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testTransformWithInvalidValue()
    {
        $transformer = new BooleanToStringTransformer();
        $transformer->transform(1);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $transformer = new BooleanToStringTransformer();
        $this->assertEquals($expected, $transformer->reverseTransform($value));
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
            ['0', false],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testReverseTransformWithNotStringValue()
    {
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform(1);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testReverseTransformWithInvalidValue()
    {
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform('test');
    }
}

<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

class MultipleValueTransformerTest extends \PHPUnit_Framework_TestCase
{
    const FIELD_DEFAULT = 'default';
    const FIELD_VALUES  = 'values';

    /**
     * @var MultipleValueTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_VALUES);
    }

    /**
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($input, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($input));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'null' => [
                'input'    => null,
                'expected' => null,
            ],
            'no default' => [
                'input'    => [
                    1 => 'string',
                    2 => new FallbackType(FallbackType::SYSTEM),
                ],
                'expected' => [
                    self::FIELD_DEFAULT => null,
                    self::FIELD_VALUES => [
                        1 => 'string',
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
            ],
            'with default' => [
                'input'    => [
                    null => 'default string',
                    1    => 'string',
                    2    => new FallbackType(FallbackType::SYSTEM),
                ],
                'expected' => [
                    self::FIELD_DEFAULT => 'default string',
                    self::FIELD_VALUES => [
                        1 => 'string',
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "DateTime" given
     */
    public function testTransformUnexpectedTypeException()
    {
        $this->transformer->transform(new \DateTime());
    }

    /**
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($input, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($input));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'null' => [
                'input'    => null,
                'expected' => null,
            ],
            'valid data' => [
                'input' => [
                    self::FIELD_DEFAULT => 'default string',
                    self::FIELD_VALUES => [
                        1 => 'string',
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
                'expected'    => [
                    null => 'default string',
                    1    => 'string',
                    2    => new FallbackType(FallbackType::SYSTEM),
                ],
            ],
            'valid data with null values' => [
                'input' => [
                    self::FIELD_DEFAULT => null,
                    self::FIELD_VALUES => [
                        1 => null,
                        2 => null,
                    ]
                ],
                'expected'    => [
                    null => null,
                    1    => null,
                    2    => null,
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "DateTime" given
     */
    public function testReverseTransformUnexpectedTypeException()
    {
        $this->transformer->reverseTransform(new \DateTime());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Value does not contain default value
     */
    public function testReverseTransformNoDefaultDataException()
    {
        $this->transformer->reverseTransform([]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Value does not contain collection value
     */
    public function testReverseTransformNoCollectionDataException()
    {
        $this->transformer->reverseTransform([self::FIELD_DEFAULT => 'default string']);
    }
}

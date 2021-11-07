<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class MultipleValueTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_DEFAULT = 'default';
    private const FIELD_VALUES = 'values';

    /** @var MultipleValueTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_VALUES);
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(mixed $input, mixed $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($input));
    }

    public function transformDataProvider(): array
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

    public function testTransformUnexpectedTypeException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "DateTime" given');

        $this->transformer->transform(new \DateTime());
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(mixed $input, mixed $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($input));
    }

    public function reverseTransformDataProvider(): array
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

    public function testReverseTransformUnexpectedTypeException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "DateTime" given');

        $this->transformer->reverseTransform(new \DateTime());
    }

    public function testReverseTransformNoDefaultDataException()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value does not contain default value');

        $this->transformer->reverseTransform([]);
    }

    public function testReverseTransformNoCollectionDataException()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value does not contain collection value');

        $this->transformer->reverseTransform([self::FIELD_DEFAULT => 'default string']);
    }
}

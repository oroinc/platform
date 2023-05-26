<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\RangeConstraintConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class RangeConstraintConverterTest extends TestCase
{
    private PropertyAccessorInterface|MockObject $propertyAccessor;

    private FormInterface|MockObject $form;

    private RangeConstraintConverter $converter;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->converter = new RangeConstraintConverter($this->propertyAccessor);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $expected, Constraint $constraint): void
    {
        self::assertSame($expected, $this->converter->supports($constraint, $this->form));
    }

    public function supportsDataProvider(): \Generator
    {
        yield [
            'expected' => true,
            'constraint' => new Range(
                [
                    'minPropertyPath' => 'abc',
                    'maxPropertyPath' => 'cba',
                ],
                payload: null
            ),
        ];

        yield [
            'expected' => false,
            'constraint' => new NotBlank(),
        ];

        yield [
            'expected' => false,
            'constraint' => new Range(
                [
                    'minPropertyPath' => 'abc',
                    'maxPropertyPath' => 'cba',
                ],
                payload: ['jsValidation' => []]
            ),
        ];
    }

    public function testConvertWithoutPropertyPaths(): void
    {
        $constraint = new Range(
            [
                'min' => 1,
                'max' => 2,
            ],
            payload: ['jsValidation' => []]
        );
        $this->propertyAccessor
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals($constraint, $this->converter->convertConstraint($constraint, $this->form));
    }

    public function testConvertWithoutFormData(): void
    {
        $constraint = new Range([
            'minPropertyPath' => 'propertyMin',
            'max' => 100,
        ]);
        $this->propertyAccessor
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals($constraint, $this->converter->convertConstraint($constraint));
    }

    public function testConvertWithJsValidationPayload(): void
    {
        $constraint = new Range([
            'minPropertyPath' => 'propertyMin',
            'maxPropertyPath' => 'propertyMax',
        ]);

        $formData = new \stdClass;
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);

        $this->form
            ->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);

        $this->propertyAccessor
            ->expects(self::exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$formData, 'propertyMin', 1],
                [$formData, 'propertyMax', 2]
            ]);

        self::assertEquals(
            new Range([
                'min' => 1,
                'max' => 2,
            ]),
            $this->converter->convertConstraint($constraint, $this->form)
        );
    }
}

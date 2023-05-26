<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\GenericConstraintConverter;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Sequentially;

class GenericConstraintConverterTest extends TestCase
{
    private ConstraintFactory|MockObject $constraintFactory;

    private ConstraintConverterInterface|MockObject $constraintConverter;

    private GenericConstraintConverter $converter;

    protected function setUp(): void
    {
        $this->constraintFactory = $this->createMock(ConstraintFactory::class);
        $this->constraintConverter = $this->createMock(ConstraintConverterInterface::class);
        $this->converter = new GenericConstraintConverter(
            $this->constraintFactory,
            $this->constraintConverter,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->converter->supports($this->createMock(Constraint::class)));
    }

    public function testConvertWithoutJsValidationPayload(): void
    {
        $constraint = new Sequentially([new NotBlank()], payload: null);
        $this->constraintFactory->expects(self::never())
            ->method('create');

        self::assertEquals($constraint, $this->converter->convertConstraint($constraint));
    }

    public function testConvertWithJsValidationPayload(): void
    {
        $type = 'Range';
        $options = [
            'minPropertyPath' => 'propertyMin',
            'maxPropertyPath' => 'propertyMax',
        ];

        $constraint = new Sequentially([new NotBlank()], payload: [
            'jsValidation' => [
                'type' => $type,
                'options' => $options,
            ],
        ]);

        $expectedConstraint = new Range($options);

        $this->constraintFactory->expects(self::once())
            ->method('create')
            ->with($type, $options)
            ->willReturn($expectedConstraint);
        $this->constraintConverter->expects(self::once())
            ->method('convertConstraint')
            ->with($expectedConstraint, null)
            ->willReturn($expectedConstraint);

        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }
}

<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverter;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;

class ConstraintConverterTest extends TestCase
{
    private ConstraintConverterInterface&MockObject $exactConverter;
    private FormInterface&MockObject $form;
    private ConstraintConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->exactConverter = $this->createMock(ConstraintConverterInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->converter = new ConstraintConverter([$this->exactConverter]);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $expected, bool $exactConverterSupports): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->exactConverter->expects(self::once())
            ->method('supports')
            ->with($constraint, $this->form)
            ->willReturn($exactConverterSupports);
        $this->exactConverter->expects(self::never())
            ->method('convertConstraint');

        self::assertSame($expected, $this->converter->supports($constraint, $this->form));
    }

    public function supportsDataProvider(): array
    {
        return [
            ['expected' => true, 'exactConverterSupports' => true],
            ['expected' => false, 'exactConverterSupports' => false]
        ];
    }

    public function testConvertConstraintWhenNoJsValidation(): void
    {
        $constraint = new Type(['type' => 'string']);

        $this->exactConverter->expects(self::once())
            ->method('supports')
            ->with($constraint, $this->form)
            ->willReturn(true);
        $this->exactConverter->expects(self::once())
            ->method('convertConstraint')
            ->with($constraint, $this->form)
            ->willReturn($constraint);

        self::assertSame($constraint, $this->converter->convertConstraint($constraint, $this->form));
    }

    public function testConvertConstraintWhenHasJsValidation(): void
    {
        $options = ['sample_key' => 'sample_value'];
        $constraintName = 'DateTime';
        $constraint = new Type(
            \DateTimeInterface::class,
            null,
            null,
            ['jsValidation' => ['type' => $constraintName, 'options' => $options]]
        );

        $this->exactConverter->expects(self::once())
            ->method('supports')
            ->with($constraint, $this->form)
            ->willReturn(true);
        $this->exactConverter->expects(self::once())
            ->method('convertConstraint')
            ->with($constraint, $this->form)
            ->willReturn($constraint);

        self::assertSame($constraint, $this->converter->convertConstraint($constraint, $this->form));
    }
}

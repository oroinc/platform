<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverter;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Type;

class ConstraintConverterTest extends TestCase
{
    private ConstraintConverterInterface|MockObject $exactConverter;

    private FormInterface|MockObject $form;

    private ConstraintConverter $converter;

    protected function setUp(): void
    {
        $this->exactConverter = $this->getMockBuilder(ConstraintConverterInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['supports'])
            ->onlyMethods(['convertConstraint'])
            ->getMock();
        $this->form = $this->createMock(FormInterface::class);
        $this->converter = new ConstraintConverter(new ConstraintFactory());
        $this->converter->setProcessors([$this->exactConverter]);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $expected, bool $exactConverterSupports): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->exactConverter
            ->expects(self::once())
            ->method('supports')
            ->with(
                $constraint,
                $this->form
            )
            ->willReturn($exactConverterSupports);
        $this->exactConverter
            ->expects(self::never())
            ->method('convertConstraint');

        self::assertSame($expected, $this->converter->supports($constraint, $this->form));
    }

    public function supportsDataProvider(): \Generator
    {
        yield [
            'expected' => true,
            'exactConverterSupports' => true,
        ];

        yield [
            'expected' => false,
            'exactConverterSupports' => false,
        ];
    }

    public function testConvertConstraintWhenNoJsValidation(): void
    {
        $constraint = new Type(['type' => 'string']);

        $this->exactConverter
            ->expects(self::once())
            ->method('supports')
            ->with(
                $constraint,
                $this->form
            )
            ->willReturn(true);
        $this->exactConverter
            ->expects(self::once())
            ->method('convertConstraint')
            ->with(
                $constraint,
                $this->form
            )
            ->willReturn($constraint);

        self::assertSame($constraint, $this->converter->convertConstraint($constraint, $this->form));
    }

    public function testConvertConstraintWhenNoProcessorsAndNoJsValidation(): void
    {
        $constraint = new Type(['type' => 'string']);

        $this->converter->setProcessors([]);
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

        $this->exactConverter
            ->expects(self::once())
            ->method('supports')
            ->with(
                $constraint,
                $this->form
            )
            ->willReturn(true);
        $this->exactConverter
            ->expects(self::once())
            ->method('convertConstraint')
            ->with(
                $constraint,
                $this->form
            )
            ->willReturn($constraint);

        self::assertSame($constraint, $this->converter->convertConstraint($constraint, $this->form));
    }

    public function testConvertConstraintWhenNoProcessorsHasJsValidation(): void
    {
        $options = ['format' => 'Y-m-d H:i'];
        $constraintName = 'DateTime';
        $constraint = new Type(
            \DateTimeInterface::class,
            null,
            null,
            ['jsValidation' => ['type' => $constraintName, 'options' => $options]]
        );

        $this->converter->setProcessors([]);

        self::assertEquals(
            new DateTime($options['format']),
            $this->converter->convertConstraint($constraint, $this->form)
        );
    }
}

<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\PercentRangeConstraintConverter;
use Oro\Bundle\FormBundle\Validator\Constraints\PercentRange;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class PercentRangeConstraintConverterTest extends TestCase
{
    private ConstraintConverterInterface|MockObject $innerConverter;

    private PercentRangeConstraintConverter $converter;

    protected function setUp(): void
    {
        $this->innerConverter = $this->getMockBuilder(ConstraintConverterInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['supports'])
            ->onlyMethods(['convertConstraint'])
            ->getMock();

        $this->converter = new PercentRangeConstraintConverter($this->innerConverter);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $expected, Constraint $constraint): void
    {
        self::assertSame($expected, $this->converter->supports($constraint));
    }

    public function supportsDataProvider(): \Generator
    {
        yield [
            'expected' => true,
            'constraint' => new PercentRange([
                'min' => -100,
                'minMessage' => 'min msg',
                'invalidMessage' => 'invalid msg',
            ]),
        ];

        yield [
            'expected' => false,
            'constraint' => new NotBlank(),
        ];
    }

    public function testPercentRangeConstraintWhenNotSupports(): void
    {
        $constraint = new Type(\DateTime::class);
        $expectedConstraint = new DateTime();
        $form = $this->createMock(FormInterface::class);
        $this->innerConverter
            ->expects(self::once())
            ->method('convertConstraint')
            ->with($constraint, $form)
            ->willReturn($expectedConstraint);

        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint, $form));
    }

    public function testPercentRangeConstraintWithMinLimit(): void
    {
        $constraint = new PercentRange([
            'min' => -100,
            'minMessage' => 'min msg',
            'invalidMessage' => 'invalid msg',
        ]);

        $expectedConstraint = new Range([
            'min' => $constraint->min,
            'minMessage' => $constraint->minMessage,
            'invalidMessage' => $constraint->invalidMessage,
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }

    public function testPercentRangeConstraintWithMaxLimit(): void
    {
        $constraint = new PercentRange([
            'max' => 100,
            'maxMessage' => 'max msg',
            'invalidMessage' => 'invalid msg',
        ]);

        $expectedConstraint = new Range([
            'max' => $constraint->max,
            'maxMessage' => $constraint->maxMessage,
            'invalidMessage' => $constraint->invalidMessage,
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }

    public function testPercentRangeConstraintWithRange(): void
    {
        $constraint = new PercentRange([
            'min' => -100,
            'max' => 100,
            'notInRangeMessage' => 'not in range msg',
            'invalidMessage' => 'invalid msg',
        ]);

        $expectedConstraint = new Range([
            'min' => $constraint->min,
            'max' => $constraint->max,
            'notInRangeMessage' => $constraint->notInRangeMessage,
            'invalidMessage' => $constraint->invalidMessage,
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }
}

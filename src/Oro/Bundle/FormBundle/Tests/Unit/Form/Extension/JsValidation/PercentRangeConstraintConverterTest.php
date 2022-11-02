<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\PercentRangeConstraintConverter;
use Oro\Bundle\FormBundle\Validator\Constraints\PercentRange;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PercentRangeConstraintConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConstraintConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerConverter;

    /** @var PercentRangeConstraintConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->innerConverter = $this->createMock(ConstraintConverterInterface::class);

        $this->converter = new PercentRangeConstraintConverter($this->innerConverter);
    }

    public function testNotPercentRangeConstraint(): void
    {
        $constraint = new NotBlank();

        $this->innerConverter->expects(self::once())
            ->method('convertConstraint')
            ->with(self::identicalTo($constraint))
            ->willReturn($constraint);

        self::assertSame($constraint, $this->converter->convertConstraint($constraint));
    }

    public function testPercentRangeConstraintWithMinLimit(): void
    {
        $constraint = new PercentRange([
            'min'            => -100,
            'minMessage'     => 'min msg',
            'invalidMessage' => 'invalid msg'
        ]);

        $this->innerConverter->expects(self::never())
            ->method('convertConstraint');

        $expectedConstraint = new Range([
            'min'            => $constraint->min,
            'minMessage'     => $constraint->minMessage,
            'invalidMessage' => $constraint->invalidMessage
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }

    public function testPercentRangeConstraintWithMaxLimit(): void
    {
        $constraint = new PercentRange([
            'max'            => 100,
            'maxMessage'     => 'max msg',
            'invalidMessage' => 'invalid msg'
        ]);

        $this->innerConverter->expects(self::never())
            ->method('convertConstraint');

        $expectedConstraint = new Range([
            'max'            => $constraint->max,
            'maxMessage'     => $constraint->maxMessage,
            'invalidMessage' => $constraint->invalidMessage
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }

    public function testPercentRangeConstraintWithRange(): void
    {
        $constraint = new PercentRange([
            'min'               => -100,
            'max'               => 100,
            'notInRangeMessage' => 'not in range msg',
            'invalidMessage'    => 'invalid msg'
        ]);

        $this->innerConverter->expects(self::never())
            ->method('convertConstraint');

        $expectedConstraint = new Range([
            'min'               => $constraint->min,
            'max'               => $constraint->max,
            'notInRangeMessage' => $constraint->notInRangeMessage,
            'invalidMessage'    => $constraint->invalidMessage
        ]);
        self::assertEquals($expectedConstraint, $this->converter->convertConstraint($constraint));
    }
}

<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation\Converters;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\Converters\PercentRangeConstraintConverter;
use Oro\Bundle\FormBundle\Validator\Constraints\PercentRange;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PercentRangeConstraintConverterTest extends TestCase
{
    private PercentRangeConstraintConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new PercentRangeConstraintConverter();
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

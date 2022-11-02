<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\PercentRange;
use Oro\Bundle\FormBundle\Validator\Constraints\PercentRangeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class PercentRangeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PercentRangeValidator
    {
        return new PercentRangeValidator();
    }

    public function testMinMessageSpecifiedForRange(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" constraint does not use the "minMessage" and "maxMessage" options'
            . ' when the "min" and "max" options are both set. Use the "notInRangeMessage" option instead.',
            PercentRange::class
        ));

        new PercentRange(['min' => -100, 'max' => 100, 'minMessage' => 'some message']);
    }

    public function testMaxMessageSpecifiedForRange(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" constraint does not use the "minMessage" and "maxMessage" options'
            . ' when the "min" and "max" options are both set. Use the "notInRangeMessage" option instead.',
            PercentRange::class
        ));

        new PercentRange(['min' => -100, 'max' => 100, 'maxMessage' => 'some message']);
    }

    public function testNoMinAndMaxLimits(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(sprintf(
            'Either option "min" or "max" must be given for the constraint "%s".',
            PercentRange::class
        ));

        new PercentRange();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(10, $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new PercentRange(['min' => 0]));
        $this->assertNoViolation();
    }

    public function testNotNumericValue(): void
    {
        $constraint = new PercentRange(['min' => 0]);
        $this->validator->validate('not a number', $constraint);
        $this->buildViolation($constraint->invalidMessage)
            ->setParameter('{{ value }}', '"not a number"')
            ->setCode(Range::INVALID_CHARACTERS_ERROR)
            ->assertRaised();
    }

    public function testIntegerValue(): void
    {
        $this->validator->validate(10, new PercentRange(['min' => 0]));
        $this->assertNoViolation();
    }

    public function testFloatValue(): void
    {
        $this->validator->validate(10.0, new PercentRange(['min' => 0]));
        $this->assertNoViolation();
    }

    public function testStringNumericValue(): void
    {
        $this->validator->validate('10', new PercentRange(['min' => 0]));
        $this->assertNoViolation();
    }

    public function testValueLessThanMaxLimit(): void
    {
        $this->validator->validate(1.10999, new PercentRange(['max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMaxLimit(): void
    {
        $this->validator->validate(1.11, new PercentRange(['max' => 111]));
        $this->assertNoViolation();
    }

    public function testRoundValueEqualsToMaxLimit(): void
    {
        $this->validator->validate(1.110000000000001, new PercentRange(['max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueGreaterThanMaxLimit(): void
    {
        $constraint = new PercentRange(['max' => 111]);
        $this->validator->validate(1.111, $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ value }}', '111.1')
            ->setParameter('{{ limit }}', '111%')
            ->setCode(Range::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testValueGreaterThanMinLimit(): void
    {
        $this->validator->validate(-1.10999, new PercentRange(['min' => -111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMinLimit(): void
    {
        $this->validator->validate(-1.11, new PercentRange(['min' => -111]));
        $this->assertNoViolation();
    }

    public function testRoundValueEqualsToMinLimit(): void
    {
        $this->validator->validate(-1.110000000000001, new PercentRange(['min' => -111]));
        $this->assertNoViolation();
    }

    public function testValueLessThanMinLimit(): void
    {
        $constraint = new PercentRange(['min' => -111]);
        $this->validator->validate(-1.111, $constraint);
        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ value }}', '-111.1')
            ->setParameter('{{ limit }}', '-111%')
            ->setCode(Range::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testValueInRangeLimit(): void
    {
        $this->validator->validate(0, new PercentRange(['min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMinRangeLimit(): void
    {
        $this->validator->validate(-1.11, new PercentRange(['min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMaxRangeLimit(): void
    {
        $this->validator->validate(1.11, new PercentRange(['min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueGreaterThanRangeLimit(): void
    {
        $constraint = new PercentRange(['min' => -111, 'max' => 111]);
        $this->validator->validate(1.111, $constraint);
        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ value }}', '111.1')
            ->setParameter('{{ min }}', '-111%')
            ->setParameter('{{ max }}', '111%')
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueLessThanRangeLimit(): void
    {
        $constraint = new PercentRange(['min' => -111, 'max' => 111]);
        $this->validator->validate(-1.111, $constraint);
        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ value }}', '-111.1')
            ->setParameter('{{ min }}', '-111%')
            ->setParameter('{{ max }}', '111%')
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueLessThanMaxLimitForFractional100Constraint(): void
    {
        $this->validator->validate(110.999, new PercentRange(['type' => 'fractional_100', 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMaxLimitForFractional100Constraint(): void
    {
        $this->validator->validate(111, new PercentRange(['type' => 'fractional_100', 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueGreaterThanMaxLimitForFractional100Constraint(): void
    {
        $constraint = new PercentRange(['type' => 'fractional_100', 'max' => 111]);
        $this->validator->validate(111.001, $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ value }}', '111.001')
            ->setParameter('{{ limit }}', '111%')
            ->setCode(Range::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testValueGreaterThanMinLimitForFractional100Constraint(): void
    {
        $this->validator->validate(-110.999, new PercentRange(['type' => 'fractional_100', 'min' => -111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMinLimitForFractional100Constraint(): void
    {
        $this->validator->validate(-111, new PercentRange(['type' => 'fractional_100', 'min' => -111]));
        $this->assertNoViolation();
    }

    public function testValueLessThanMinLimitForFractional100Constraint(): void
    {
        $constraint = new PercentRange(['type' => 'fractional_100', 'min' => -111]);
        $this->validator->validate(-111.001, $constraint);
        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ value }}', '-111.001')
            ->setParameter('{{ limit }}', '-111%')
            ->setCode(Range::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testValueInRangeLimitForFractional100Constraint(): void
    {
        $this->validator->validate(0, new PercentRange(['type' => 'fractional_100', 'min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMinRangeLimitForFractional100Constraint(): void
    {
        $this->validator->validate(-111, new PercentRange(['type' => 'fractional_100', 'min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMaxRangeLimitForFractional100Constraint(): void
    {
        $this->validator->validate(111, new PercentRange(['type' => 'fractional_100', 'min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueGreaterThanRangeLimitForFractional100Constraint(): void
    {
        $constraint = new PercentRange(['type' => 'fractional_100', 'min' => -111, 'max' => 111]);
        $this->validator->validate(111.001, $constraint);
        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ value }}', '111.001')
            ->setParameter('{{ min }}', '-111%')
            ->setParameter('{{ max }}', '111%')
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueLessThanRangeLimitForFractional100Constraint(): void
    {
        $constraint = new PercentRange(['type' => 'fractional_100', 'min' => -111, 'max' => 111]);
        $this->validator->validate(-111.001, $constraint);
        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ value }}', '-111.001')
            ->setParameter('{{ min }}', '-111%')
            ->setParameter('{{ max }}', '111%')
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueLessThanMaxLimitForIntegerConstraint(): void
    {
        $this->validator->validate(110, new PercentRange(['type' => 'integer', 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMaxLimitForIntegerConstraint(): void
    {
        $this->validator->validate(111, new PercentRange(['type' => 'integer', 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueGreaterThanMaxLimitForIntegerConstraint(): void
    {
        $constraint = new PercentRange(['type' => 'integer', 'max' => 111]);
        $this->validator->validate(112, $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ value }}', '112')
            ->setParameter('{{ limit }}', '111%')
            ->setCode(Range::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testValueGreaterThanMinLimitForIntegerConstraint(): void
    {
        $this->validator->validate(-110, new PercentRange(['type' => 'integer', 'min' => -111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMinLimitForIntegerConstraint(): void
    {
        $this->validator->validate(-111, new PercentRange(['type' => 'integer', 'min' => -111]));
        $this->assertNoViolation();
    }

    public function testValueLessThanMinLimitForIntegerConstraint(): void
    {
        $constraint = new PercentRange(['type' => 'integer', 'min' => -111]);
        $this->validator->validate(-112, $constraint);
        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ value }}', '-112')
            ->setParameter('{{ limit }}', '-111%')
            ->setCode(Range::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testValueInRangeLimitForIntegerConstraint(): void
    {
        $this->validator->validate(0, new PercentRange(['type' => 'integer', 'min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMinRangeLimitForIntegerConstraint(): void
    {
        $this->validator->validate(-111, new PercentRange(['type' => 'integer', 'min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueEqualsToMaxRangeLimitForIntegerConstraint(): void
    {
        $this->validator->validate(111, new PercentRange(['type' => 'integer', 'min' => -111, 'max' => 111]));
        $this->assertNoViolation();
    }

    public function testValueGreaterThanRangeLimitForIntegerConstraint(): void
    {
        $constraint = new PercentRange(['type' => 'integer', 'min' => -111, 'max' => 111]);
        $this->validator->validate(112, $constraint);
        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ value }}', '112')
            ->setParameter('{{ min }}', '-111%')
            ->setParameter('{{ max }}', '111%')
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueLessThanRangeLimitForIntegerConstraint(): void
    {
        $constraint = new PercentRange(['type' => 'integer', 'min' => -111, 'max' => 111]);
        $this->validator->validate(-112, $constraint);
        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ value }}', '-112')
            ->setParameter('{{ min }}', '-111%')
            ->setParameter('{{ max }}', '111%')
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueHasFractionalPartForIntegerConstraint(): void
    {
        $constraint = new PercentRange(['type' => 'integer', 'min' => -111, 'max' => 111]);
        $this->validator->validate(11.001, $constraint);
        $this->buildViolation($constraint->notIntegerMessage)
            ->setParameter('{{ value }}', '11.001')
            ->setCode(Range::INVALID_CHARACTERS_ERROR)
            ->assertRaised();
    }
}

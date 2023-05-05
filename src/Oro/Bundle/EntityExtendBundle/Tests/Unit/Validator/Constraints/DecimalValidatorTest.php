<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DecimalValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DecimalValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new DecimalValidator();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        array $options,
        $value,
        bool $valid,
        ?string $expectedValidationMessageType = null
    ): void {
        $constraint = new Decimal($options);
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            if (null === $expectedValidationMessageType) {
                $expectedValidationMessageType = 'message';
            }
            $message = PropertyAccess::createPropertyAccessor()
                ->getValue($constraint, $expectedValidationMessageType);
            if ('message' === $expectedValidationMessageType) {
                $this->buildViolation($message)
                    ->setParameters([
                        '{{ precision }}' => $options['precision'] ?? 10,
                        '{{ scale }}'     => $options['scale'] ?? 0
                    ])
                    ->assertRaised();
            } else {
                $this->buildViolation($message)
                    ->assertRaised();
            }
        }
    }

    public function validateDataProvider(): array
    {
        return [
            [['precision' => 10, 'scale' => 4], 171.9, true],
            [['precision' => 4, 'scale' => 2], 42, true],
            [['precision' => 4, 'scale' => 2], 142, false],
            [['precision' => 4, 'scale' => 2], 42.42, true],
            [['precision' => 4, 'scale' => 2], 42.423, false],
            [['precision' => 4, 'scale' => null], 42, true],
            [['precision' => 4, 'scale' => null], 14214, false],
            [['precision' => 4, 'scale' => null], 42.42, false],
            [['precision' => 4, 'scale' => null], 42.423, false],
            [['precision' => null, 'scale' => 2], 42424242, true],
            [['precision' => null, 'scale' => 2], 424242424, false],
            [['precision' => null, 'scale' => 2], 42.42, true],
            [['precision' => null, 'scale' => 2], 42.423, false],
            [['precision' => null, 'scale' => null], 42, true],
            [['precision' => null, 'scale' => null], 2147483646, true],
            [['precision' => null, 'scale' => null], 42.42, false],
            [['precision' => null, 'scale' => null], 42.423, false],
            [['precision' => 2, 'scale' => 2], 12345678912345789123, false, 'messageNotNumeric'],
        ];
    }
}

<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class OptionalPriceValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new Constraints\OptionalPriceValidator();
    }

    public function testConfiguration()
    {
        $constraint = new Constraints\OptionalPrice();
        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(bool $isValid, Price $inputData)
    {
        $constraint = new Constraints\OptionalPrice();
        $this->validator->validate($inputData, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->atPath('property.path.currency')
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            'empty data' => [
                'isValid'   => true,
                'inputData' => Price::create(null, null),
            ],
            'empty value' => [
                'isValid'   => true,
                'inputData' => Price::create(null, 'USD'),
            ],
            'empty currency' => [
                'isValid'   => false,
                'inputData' => Price::create(11, null),
            ],
            'valid value' => [
                'isValid'   => true,
                'inputData' => Price::create(11, 'USD'),
            ],
        ];
    }
}

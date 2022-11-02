<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class OptionalPriceValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): Constraints\OptionalPriceValidator
    {
        return new Constraints\OptionalPriceValidator();
    }

    public function testGetTargets()
    {
        $constraint = new Constraints\OptionalPrice();
        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    /**
     * @dataProvider validValueDataProvider
     */
    public function testValidateValidValue(Price $value)
    {
        $constraint = new Constraints\OptionalPrice();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validValueDataProvider(): array
    {
        return [
            'empty data' => [Price::create(null, null)],
            'empty value' => [Price::create(null, 'USD')],
            'valid value' => [Price::create(11, 'USD')],
        ];
    }

    public function testValidateEmptyCurrency()
    {
        $value = Price::create(11, null);

        $constraint = new Constraints\OptionalPrice();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.currency')
            ->assertRaised();
    }
}

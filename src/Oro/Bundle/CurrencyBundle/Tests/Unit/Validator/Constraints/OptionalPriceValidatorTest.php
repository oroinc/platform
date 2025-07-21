<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Validator\Constraints\OptionalPrice;
use Oro\Bundle\CurrencyBundle\Validator\Constraints\OptionalPriceValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class OptionalPriceValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): OptionalPriceValidator
    {
        return new OptionalPriceValidator();
    }

    public function testGetTargets()
    {
        $constraint = new OptionalPrice();
        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    /**
     * @dataProvider validValueDataProvider
     */
    public function testValidateValidValue(Price $value)
    {
        $constraint = new OptionalPrice();
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

        $constraint = new OptionalPrice();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.currency')
            ->assertRaised();
    }
}

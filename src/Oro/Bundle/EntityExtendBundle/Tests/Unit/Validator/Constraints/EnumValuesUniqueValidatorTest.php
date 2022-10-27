<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumValuesUnique;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumValuesUniqueValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EnumValuesUniqueValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new EnumValuesUniqueValidator();
    }

    public function testValidateEmptyValue()
    {
        $value = [];

        $this->validator->validate($value, new EnumValuesUnique());

        $this->assertNoViolation();
    }

    public function testValidateValueWithoutDuplicates()
    {
        $value = [
            ['id' => 1, 'label' => 'first'],
            ['id' => 2, 'label' => 'second'],
            ['id' => 3, 'label' => 'third']
        ];

        $this->validator->validate($value, new EnumValuesUnique());

        $this->assertNoViolation();
    }

    public function testValidateValueWithOneDuplicate()
    {
        $constraint = new EnumValuesUnique();
        $value = [
            ['id' => 1, 'label' => 'first'],
            ['id' => 2, 'label' => 'second'],
            ['id' => 3, 'label' => 'first']
        ];

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', "'first'")
            ->setPlural(1)
            ->assertRaised();
    }

    public function testValidateValueWithSeveralDuplicates()
    {
        $constraint = new EnumValuesUnique();
        $value = [
            ['id' => 1, 'label' => 'first'],
            ['id' => 2, 'label' => 'second'],
            ['id' => 3, 'label' => 'second'],
            ['id' => 4, 'label' => 'second'],
            ['id' => 5, 'label' => 'first'],
            ['id' => 6, 'label' => 'third']
        ];

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', "'first', 'second'")
            ->setPlural(2)
            ->assertRaised();
    }

    public function testOptionComparisonShouldBeCaseInsensitive()
    {
        $value = [
            ['id' => 1, 'label' => 'first'],
            ['id' => 2, 'label' => 'second'],
            ['id' => 3, 'label' => 'First']
        ];

        $this->validator->validate($value, new EnumValuesUnique());

        $this->assertNoViolation();
    }
}
